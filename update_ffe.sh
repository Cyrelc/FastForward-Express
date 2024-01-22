#!/bin/bash

#Function to request and validate admin password
request_password() {
    while true; do
        echo "Please enter admin password for maintenance tasks:"
        read -s admin_password

        # Check if the password is correct by running a harmless command
        echo $admin_password | sudo -S true 2>/dev/null
        if [ $? -eq 0 ]; then
            break
        else
            echo "Incorrect password, please try again."
        fi
    done
}

#Request and validate password
request_password

# Pull the latest changes from the master branch
git pull origin master

# Install/update PHP dependencies
composer update

# Install/update Node.js dependencies
npm install

# Clear Laravel caches
# php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear

# Restart Laravel queue workers
php artisan queue:restart

# Supervisor tasks reset with admin privileges
echo $admin_password | sudo -S supervisorctl reread
echo $admin_password | sudo -S supervisorctl update
echo $admin_password | sudo -S supervisorctl restart all

# Restart apache service to grab new config settings
echo $admin_password | sudo -S service apache2 restart

# Build assets for production
npm run prod

echo "Update process complete."
