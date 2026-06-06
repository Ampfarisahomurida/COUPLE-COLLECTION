#!/usr/bin/env bash
# Simple deploy script for demo purposes
set -e
ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
echo "Deploying from $ROOT_DIR"
# Run DB seed (ensure config.php has DB credentials)
php "$ROOT_DIR/backend/php/seed.php"
echo "Seed completed"
# Ensure uploads directory permissions
mkdir -p "$ROOT_DIR/backend/php/uploads"
chmod 755 "$ROOT_DIR/backend/php/uploads"

echo "Done. Start the PHP server for local testing:"
echo "php -S localhost:8000 -t $ROOT_DIR/backend/php"
