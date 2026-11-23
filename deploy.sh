#!/usr/bin/env bash
set -e
ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT_DIR"

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker Compose is required. Install Docker and try again." >&2
  exit 1
fi

if [ ! -f .env ]; then
  cp .env.example .env
  echo "Created .env from the example. Replace the database/admin passwords, then run this script again." >&2
  exit 1
fi

docker compose up --build -d
docker compose exec php php backend/php/seed.php
echo "Couple Collection is available at http://localhost:8080"
