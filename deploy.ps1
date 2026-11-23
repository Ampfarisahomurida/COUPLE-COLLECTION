$ErrorActionPreference = 'Stop'
$Root = $PSScriptRoot
Push-Location $Root
try {
    if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
        throw 'Docker Compose is required. Install Docker Desktop and try again.'
    }

    if (-not (Test-Path '.env')) {
        Copy-Item '.env.example' '.env'
        throw 'Created .env from the example. Replace the database/admin passwords, then run this script again.'
    }

    docker compose up --build -d
    if ($LASTEXITCODE -ne 0) { throw 'Docker Compose failed to start.' }

    docker compose exec php php backend/php/seed.php
    if ($LASTEXITCODE -ne 0) { throw 'Database seeding failed.' }

    Write-Output 'Couple Collection is available at http://localhost:8080'
} finally {
    Pop-Location
}
