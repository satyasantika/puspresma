#!/bin/sh
# Dijalankan entrypoint image nginx saat start: menurunkan awalan subpath dari APP_URL
# (mis. https://supportfkip.unsil.ac.id/puspresma -> /puspresma) dan membuang awalan itu bila reverse proxy
# meneruskannya apa adanya. Bila proxy sudah memotong awalan, aturan ini tidak pernah cocok (tidak berbahaya).
set -eu

awalan=$(printf '%s' "${APP_URL:-}" | sed -n 's#^[a-zA-Z][a-zA-Z0-9+.-]*://[^/]*\(/[^?#]*\)$#\1#p' | sed 's#/*$##')
berkas=/etc/nginx/awalan.conf

if [ -n "$awalan" ] && printf '%s' "$awalan" | grep -Eq '^/[A-Za-z0-9._/-]+$'; then
    {
        echo "rewrite ^${awalan}\$ / last;"
        echo "rewrite ^${awalan}/(.*)\$ /\$1 last;"
    } > "$berkas"
    echo "awalan subpath: $awalan"
else
    : > "$berkas"
fi
