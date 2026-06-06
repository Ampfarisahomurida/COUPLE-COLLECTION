# Simple deploy script for Windows PowerShell
$Root = Split-Path -Parent $PSScriptRoot
Write-Output "Deploying from $Root"
php "$Root\backend\php\seed.php"
Write-Output "Seed completed"
if(-not (Test-Path "$Root\backend\php\uploads")) { New-Item -ItemType Directory -Path "$Root\backend\php\uploads" }
Write-Output "Done. Start the PHP server for local testing:"
Write-Output "php -S localhost:8000 -t $Root\backend\php"
