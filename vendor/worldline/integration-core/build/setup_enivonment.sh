#!/bin/bash

# Exit on any error
set -e

# Update package lists
sudo apt-get update

# Install Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"

# Verify Composer installations
composer --version

# Install Composer dependencies.
composer install