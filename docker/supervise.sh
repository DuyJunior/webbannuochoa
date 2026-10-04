#!/usr/bin/env bash
set -Eeuo pipefail
cd /var/www/html

declare -a children=()
declare -A names=()
nginx_pid=''
fpm_pid=''
worker_pid=''
scheduler_pid=''
stopping=false

shutdown() {
    local exit_code="$1"
    if [[ "$stopping" == "true" ]]; then
        return
    fi
    stopping=true
    trap '' TERM INT
    printf '%s\n' 'Stopping web, queue worker and scheduler...'

    # Nginx/FPM finish current HTTP requests. Laravel finishes the active job.
    [[ -z "$nginx_pid" ]] || kill -QUIT "$nginx_pid" 2>/dev/null || true
    [[ -z "$fpm_pid" ]] || kill -QUIT "$fpm_pid" 2>/dev/null || true
    [[ -z "$worker_pid" ]] || kill -TERM -- "-$worker_pid" 2>/dev/null || true
    [[ -z "$scheduler_pid" ]] || kill -TERM -- "-$scheduler_pid" 2>/dev/null || true

    local deadline=$((SECONDS + 45))
    local alive pid
    while (( SECONDS < deadline )); do
        alive=false
        for pid in "${children[@]}"; do
            if kill -0 "$pid" 2>/dev/null; then
                alive=true
            fi
        done
        [[ "$alive" == "true" ]] || break
        sleep 1
    done

    # Each service has its own session/process group. Also stop its descendants.
    for pid in "${children[@]}"; do
        kill -KILL -- "-$pid" 2>/dev/null || true
        wait "$pid" 2>/dev/null || true
    done
    exit "$exit_code"
}

trap 'shutdown 0' TERM INT
trap 'shutdown 1' ERR

setsid php-fpm --nodaemonize &
fpm_pid=$!
children+=("$fpm_pid")
names["$fpm_pid"]='php-fpm'

setsid nginx -g 'daemon off;' &
nginx_pid=$!
children+=("$nginx_pid")
names["$nginx_pid"]='nginx'

setsid gosu www-data php artisan queue:work database --queue=default,ai-chat --sleep=2 --tries=10 --timeout=40 --memory=96 --no-interaction &
worker_pid=$!
children+=("$worker_pid")
names["$worker_pid"]='email, SePay and ai-chat worker'

setsid gosu www-data php artisan schedule:work --no-interaction &
scheduler_pid=$!
children+=("$scheduler_pid")
names["$scheduler_pid"]='scheduler'

printf '%s\n' 'Web, email/SePay/AI database queue worker and scheduler started.'

# Any exit, including exit 0, is unexpected until Render signals shutdown.
# Fail the container so a healthy HTTP process cannot mask a dead worker.
finished_pid=''
exit_status=0
wait -n -p finished_pid "${children[@]}" || exit_status=$?
printf 'Critical process exited: %s (status %s).\n' "${names[${finished_pid:-0}]:-unknown}" "$exit_status" >&2
shutdown 1
