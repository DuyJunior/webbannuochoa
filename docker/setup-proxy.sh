#!/usr/bin/env bash
# One-time, operator-run setup for soopi.site only. Requires sudo and live DNS.
set -Eeuo pipefail
[[ "$EUID" == 0 ]] || { echo 'Run this script with sudo.' >&2; exit 1; }
domain=soopi.site
expected_ip=103.28.32.141
site=/etc/nginx/sites-available/soopi.site
link=/etc/nginx/sites-enabled/soopi.site
webroot=/var/lib/soopi-acme
marker='# Managed for duyd92689-debug/webbannuochoa only'

command -v certbot >/dev/null
command -v nginx >/dev/null
getent ahostsv4 "$domain" | awk '{print $1}' | sort -u | grep -Fxq "$expected_ip" || {
    echo "Point the A record for $domain to $expected_ip first." >&2
    exit 1
}
if [[ -e "$site" ]] && ! grep -Fxq "$marker" "$site"; then
    echo 'Existing soopi.site configuration is not managed by this project; refusing to overwrite.' >&2
    exit 1
fi
if [[ -L "$link" && "$(readlink "$link")" != "$site" ]] || [[ -e "$link" && ! -L "$link" ]]; then
    echo 'Unexpected enabled-site file; refusing to overwrite.' >&2
    exit 1
fi
nginx -t
install -d -m 755 "$webroot"
cat > "$site" <<'NGINX'
# Managed for duyd92689-debug/webbannuochoa only
server {
    listen 80;
    listen [::]:80;
    server_name soopi.site;
    location ^~ /.well-known/acme-challenge/ { root /var/lib/soopi-acme; }
    location / { return 503; }
}
NGINX
ln -sfn "$site" "$link"
nginx -t
systemctl reload nginx
certbot certonly --webroot -w "$webroot" -d "$domain" --non-interactive \
    --agree-tos --register-unsafely-without-email --deploy-hook 'systemctl reload nginx'
cat > "$site" <<'NGINX'
# Managed for duyd92689-debug/webbannuochoa only
server {
    listen 80;
    listen [::]:80;
    server_name soopi.site;
    location ^~ /.well-known/acme-challenge/ { root /var/lib/soopi-acme; }
    location / { return 301 https://soopi.site$request_uri; }
}
server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name soopi.site;
    ssl_certificate /etc/letsencrypt/live/soopi.site/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/soopi.site/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    client_max_body_size 25m;
    location / {
        proxy_pass http://127.0.0.1:18082;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $remote_addr;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Port $server_port;
        proxy_read_timeout 120s;
    }
}
NGINX
nginx -t
systemctl reload nginx
echo 'soopi.site HTTPS proxy is configured. Existing virtual hosts were not edited.'
