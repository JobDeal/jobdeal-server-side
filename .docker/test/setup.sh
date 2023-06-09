#!/usr/bin/env bash

# Change access rights for the Laravel folders
# in order to make Laravel able to access
# cache and logs folder.
chgrp -R www-data storage bootstrap/cache && \
    chown -R www-data storage bootstrap/cache && \
    chmod -R ug+rwx storage bootstrap/cache && \
# Create log file for Laravel and give it write access
# www-data is a standard apache user that must have an
# access to the folder structure

cp .env.testing .env
cat .env
touch storage/logs/laravel.log && chmod 775 storage/logs/laravel.log && chown www-data storage/logs/laravel.log
COMPOSER_MEMORY_LIMIT=-1 yes | composer install
# npm install
php artisan --env=testing key:generate
yes | php artisan migrate:fresh --seed --env=testing && echo "Done..."
