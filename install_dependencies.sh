#!/bin/bash

#Update the package list

#Install composer
if ! command -v composer &> /dev/null
then
    echo "Composer could not be found, installing now..."
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
else
    echo "Composer is already installed"
fi

if ! command -v node &> /dev/null
then
    echo "node could not be found, installing now..."
    sudo apt-get install nodejs
else
    echo "node is already installed"
fi

if ! command -v npm &> /dev/null
then
    echo "npm could not be found, installing now..."
    sudo apt-get install npm
else
    echo "npm is already installed"
fi

