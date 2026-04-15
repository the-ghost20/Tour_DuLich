#!/usr/bin/env bash
# Chạy PHP built-in server (cần PHP trên PATH: brew install php hoặc XAMPP)
# Truy cập: http://127.0.0.1:8080/frontend/index.php
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"
exec php -S 127.0.0.1:8080 -t "$ROOT"
