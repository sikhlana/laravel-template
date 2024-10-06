#!/usr/bin/env ash

set -Eeuo pipefail
source .env

if [[ "$APP_ENV" == "production" ]]; then
    composer install --ansi --no-interaction --no-progress --no-scripts --no-dev
    composer clear-cache
fi

composer run post-autoload-dump --ansi

php artisan storage:link
php artisan optimize

# shellcheck disable=SC2046
export $(xargs < .env)

find public/ -type f \( -name "*.js" -o -name "*.css" \) -print0 | while read -d $'\0' file; do
    # shellcheck disable=SC2016,SC2046
    envsubst "$(printf '${%s} ' $(cut -d'=' -f1 < .env))" < "$file" | sponge "$file"
done

exec php artisan "$@"
