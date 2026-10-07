#!/usr/bin/env bash
# Called only for this application; never prune Docker or manage other projects.
# The only Docker cleanup is removing this app's own superseded image digests.
set -Eeuo pipefail
umask 0077

ROOT=/home/hieu/soopi
PROJECT=soopi
image="${1:?Image digest required}"
release="${2:?Release directory required}"
registry_user="${3:?Docker Hub username required}"

fail() { printf '%s\n' "$*" >&2; exit 1; }
[[ "$image" =~ ^docker\.io/[a-z0-9_-]+/webbannuochoa@sha256:[a-f0-9]{64}$ ]] || fail 'Invalid immutable image reference.'
[[ "$registry_user" =~ ^[a-z0-9][a-z0-9_-]*$ ]] || fail 'Invalid registry username.'
[[ "$release" =~ ^/home/hieu/soopi/releases/[a-f0-9]{40}$ ]] || fail 'Release outside Soopi directory.'
[[ ! -L "$ROOT" && "$(realpath "$release")" == "$release" ]] || fail 'Unexpected deployment symlink.'
[[ "$(cat "$ROOT/.deployment-owner")" == duyd92689-debug/webbannuochoa ]] || fail 'Deployment ownership marker missing.'
[[ -f "$ROOT/.env" && ! -L "$ROOT/.env" ]] || fail 'Production .env has not been provisioned yet.'

exec 9>"$ROOT/.deploy.lock"
flock -n 9 || fail 'Another Soopi deployment is running.'
cd "$ROOT"

# Remove only this app's superseded images so they cannot fill the disk. Keep the
# running and target images; older digests stay in .previous-image and on Docker Hub
# for re-pulling. docker rmi without -f also refuses images any container uses.
repository="${image%@*}"
keep_ids=()
for ref in "$image" "$(cat "$ROOT/.deployed-image" 2>/dev/null)"; do
    [[ "$ref" =~ ^docker\.io/[a-z0-9_-]+/webbannuochoa@sha256:[a-f0-9]{64}$ ]] || continue
    keep_ids+=("$(docker image inspect --format '{{.Id}}' "$ref" 2>/dev/null || true)")
done
while read -r id; do
    [[ " ${keep_ids[*]} " == *" $id "* ]] && continue
    if docker rmi "$id" >/dev/null 2>&1; then
        printf 'Removed superseded Soopi image %s\n' "${id:7:12}"
    fi
done < <(docker image ls --no-trunc --format '{{.ID}}' "$repository" | sort -u)

# Leave room for image unpacking, MySQL, and backups; never auto-delete shared data.
free_kb=$(df -Pk "$ROOT" | awk 'NR==2 {print $4}')
(( free_kb >= 4 * 1024 * 1024 )) || fail 'Need at least 4 GiB free before deployment; only superseded Soopi images were removed.'

auth_dir=$(mktemp -d "$ROOT/.docker-auth.XXXXXX")
backup_container=''
maintenance=false
cleanup() {
    status=$?
    if [[ -n "$backup_container" ]]; then
        docker rm "$backup_container" >/dev/null 2>&1 || true
    fi
    rm -f "$auth_dir/config.json"
    rmdir "$auth_dir" 2>/dev/null || true
    if (( status != 0 )); then
        printf '%s\n' 'Deployment failed. No database rollback or shared Docker cleanup was attempted.' >&2
        if [[ "$maintenance" == true ]]; then
            printf '%s\n' 'Soopi is in maintenance; inspect migration/backup before resuming or rolling back.' >&2
        fi
    fi
}
trap cleanup EXIT

# Token arrives on stdin, never as a command argument or a saved shared credential.
docker --config "$auth_dir" login --username "$registry_user" --password-stdin >/dev/null
export APP_IMAGE="$image" APP_ENV_FILE="$ROOT/.env"
compose() {
    docker --config "$auth_dir" compose --env-file "$ROOT/.env" \
        --project-name "$PROJECT" --project-directory "$release" \
        -f "$release/compose.yaml" -f "$release/compose.host.yaml" "$@"
}
compose config --quiet

# Refuse first deployment if another process owns the chosen loopback port.
existing=$(docker ps -q --filter label=com.docker.compose.project="$PROJECT" --filter label=com.docker.compose.service=app)
if [[ -z "$existing" ]] && ss -H -ltn 'sport = :18082' | grep -q .; then
    fail 'Port 18082 is already in use; no services were changed.'
fi

compose pull app db
# Validate production config before touching the currently running application.
compose run --rm --no-deps --entrypoint php app docker/check-compose-runtime.php

if [[ -n "$existing" ]]; then
    stamp=$(date -u +%Y%m%dT%H%M%SZ)
    backup="$ROOT/backups/$stamp"
    mkdir -p "$backup"
    docker exec --user www-data "$existing" php artisan down --retry=60
    maintenance=true
    # Stop the entire app so workers cannot change DB/uploads during the snapshot.
    compose stop app
    # Expand credentials inside the database container, never in the host shell.
    # shellcheck disable=SC2016
    compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysqldump -uroot --single-transaction --no-tablespaces --routines --events --triggers --set-gtid-purged=OFF "$MYSQL_DATABASE"' | gzip > "$backup/database.sql.gz"
    gzip -t "$backup/database.sql.gz"
    # Capture the app's existing volumes without starting the old application.
    backup_container="soopi-backup-$stamp"
    docker create --name "$backup_container" --volumes-from "$existing":ro --entrypoint tar "$image" \
        -czf /tmp/uploads.tar.gz -C /var/www/html storage public/images/products public/images/videos public/images/reviews >/dev/null
    docker start -a "$backup_container"
    [[ "$(docker inspect --format '{{.State.ExitCode}}' "$backup_container")" == 0 ]] || fail 'Upload backup failed.'
    docker cp "$backup_container:/tmp/uploads.tar.gz" "$backup/uploads.tar.gz"
    docker rm "$backup_container" >/dev/null
    backup_container=''
    cp "$ROOT/.env" "$backup/environment.env"
fi

# No down, --remove-orphans, prune, or host reverse-proxy changes on this VPS.
# /up can boot under maintenance; public traffic is restored after containers pass.
compose up -d --no-build --wait --wait-timeout 240 app db
compose exec -T --user www-data app php artisan up
maintenance=false
curl --fail --silent --show-error --retry 5 --retry-delay 3 http://127.0.0.1:18082/up >/dev/null
curl --fail --silent --show-error http://127.0.0.1:18082/ >/dev/null

if [[ -f "$ROOT/.deployed-image" ]]; then
    cp "$ROOT/.deployed-image" "$ROOT/.previous-image"
fi
printf '%s\n' "$image" > "$ROOT/.deployed-image"
printf '%s\n' "$release" > "$ROOT/.deployed-release"
printf '%s\n' 'Soopi deployment healthy on 127.0.0.1:18082. Other Compose projects were not changed.'
