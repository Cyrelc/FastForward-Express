#!/bin/bash

# Pull the latest changes from the master branch
git pull origin master

# Install/update PHP dependencies
composer install

# Install/update Node.js dependencies
npm install

# Clear Laravel caches
# php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear

# Restart Laravel queue workers
php artisan queue:restart

# Build assets for production
npm run prod

echo "Update process complete."
