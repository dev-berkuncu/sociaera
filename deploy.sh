#!/bin/bash
# Sociaera Deploy Script
# Sunucuda çalıştır: bash deploy.sh

ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"
ENV_FILE="$ROOT_DIR/.env"
ENV_BACKUP="$ROOT_DIR/.env.backup"

echo "=== Sociaera Deploy ==="

# 1. .env varsa yedekle
if [ -f "$ENV_FILE" ]; then
    cp "$ENV_FILE" "$ENV_BACKUP"
    echo "[OK] .env yedeklendi"
else
    echo "[WARN] .env bulunamadı, backup yok"
fi

# 2. Git pull
git pull origin main
echo "[OK] Git pull tamamlandı"

# 3. .env geri yükle
if [ -f "$ENV_BACKUP" ]; then
    cp "$ENV_BACKUP" "$ENV_FILE"
    echo "[OK] .env geri yüklendi"
elif [ ! -f "$ENV_FILE" ]; then
    echo "[ERROR] .env yok ve backup da yok! Lütfen .env dosyasını manuel yükleyin."
    exit 1
fi

echo "=== Deploy tamamlandı ==="
