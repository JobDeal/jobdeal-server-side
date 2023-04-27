#!/usr/bin/env bash
COMPOSER_MEMORY_LIMIT=-1 yes | composer install
# npm install
php artisan --env=development key:generate
yes | php artisan migrate:fresh --seed --env=development && echo "Done..."
tail /var/log/apache2/error.log -f