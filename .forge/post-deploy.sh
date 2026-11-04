#!/usr/bin/env bash
set -euo pipefail

# Run after Forge has checked out the new code and installed its dependencies.
cd "$(dirname "${BASH_SOURCE[0]}")/.."

app_php="${FORGE_PHP:-php}"
"$app_php" artisan migrate --force --no-interaction
"$app_php" artisan optimize:clear
"$app_php" artisan storage:link --force
