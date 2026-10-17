#!/usr/bin/env bash
set -euo pipefail

repo_dir=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
web_root=${GRABER_HUB_WEB_ROOT:-/var/www/html}
data_dir=${GRABER_HUB_DATA_DIR:-/var/lib/graber-hub}
timezone=${GRABER_HUB_TIMEZONE:-America/New_York}

if [ "$(id -u)" -eq 0 ]; then
    sudo_cmd=()
else
    sudo_cmd=(sudo)
fi

"${sudo_cmd[@]}" apt-get update
"${sudo_cmd[@]}" apt-get install -y lighttpd php-cgi jq curl
"${sudo_cmd[@]}" lighty-enable-mod fastcgi fastcgi-php
"${sudo_cmd[@]}" install -d -o www-data -g www-data -m 0750 "$data_dir" "$web_root/uploads"
"${sudo_cmd[@]}" install -o www-data -g www-data -m 0644 \
    "$repo_dir/web/api.php" \
    "$repo_dir/web/index.php" \
    "$repo_dir/web/photos.php" \
    "$repo_dir/web/voice.php" \
    "$web_root/"

if [ ! -f "$data_dir/data.json" ]; then
    "${sudo_cmd[@]}" install -o www-data -g www-data -m 0640 \
        "$repo_dir/web/data.example.json" "$data_dir/data.json"
fi

config_file=$(mktemp)
trap 'rm -f "$config_file"' EXIT
printf '%s\n' \
    'server.modules += ( "mod_setenv" )' \
    'setenv.add-environment = (' \
    "  \"GRABER_HUB_DATA_FILE\" => \"$data_dir/data.json\"," \
    "  \"GRABER_HUB_PHOTO_SETTINGS_FILE\" => \"$data_dir/photo-settings.json\"," \
    "  \"GRABER_HUB_PHOTOS_FILE\" => \"$data_dir/photos.json\"," \
    "  \"GRABER_HUB_UPLOAD_DIR\" => \"$web_root/uploads\"," \
    "  \"GRABER_HUB_TIMEZONE\" => \"$timezone\"" \
    ')' > "$config_file"

"${sudo_cmd[@]}" install -m 0644 "$config_file" /etc/lighttpd/conf-available/99-graber-hub.conf
"${sudo_cmd[@]}" ln -sfn ../conf-available/99-graber-hub.conf /etc/lighttpd/conf-enabled/99-graber-hub.conf
"${sudo_cmd[@]}" chown -R www-data:www-data "$data_dir" "$web_root/uploads"
"${sudo_cmd[@]}" lighttpd -tt -f /etc/lighttpd/lighttpd.conf
"${sudo_cmd[@]}" systemctl restart lighttpd

curl -fsS http://127.0.0.1/api.php/heartbeat | jq -e '.dashboard.kids | type == "array"' >/dev/null
echo "Graber Hub is running at http://$(hostname -I | awk '{print $1}')/"
