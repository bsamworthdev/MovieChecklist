#!/usr/bin/env bash
set -euo pipefail

cd /home/forge/moviechecklist.co.uk

git fetch origin "$FORGE_SITE_BRANCH"
if [ -n "${FORGE_VAR_TESTED_COMMIT:-}" ]; then
    # Do not let a slower, older CI run replace a newer deployment.
    if [ "$FORGE_VAR_TESTED_COMMIT" != "$(git rev-parse "origin/$FORGE_SITE_BRANCH")" ]; then
        echo 'Skipping superseded deployment; a newer master commit exists.'
        exit 0
    fi
    git checkout --detach "$FORGE_VAR_TESTED_COMMIT"
else
    # Keep manual Forge deployments available.
    git checkout "$FORGE_SITE_BRANCH"
    git pull --ff-only origin "$FORGE_SITE_BRANCH"
fi

"$FORGE_COMPOSER" install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Migrate and clear caches before reloading PHP-FPM.
bash .forge/post-deploy.sh

# Preserve the existing lock that prevents concurrent PHP-FPM reloads.
touch /tmp/fpmlock 2>/dev/null || true
(
    flock -w 10 9 || exit 1
    echo 'Reloading PHP FPM...'
    sudo -S service "$FORGE_PHP_FPM" reload
) 9</tmp/fpmlock
