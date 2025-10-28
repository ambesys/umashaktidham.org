#!/bin/bash

# This script sets up the Uma Shakti Dham application environment.

# Update package list and install necessary packages
sudo apt update
sudo apt install -y php php-mysql php-xml php-mbstring php-curl

# Set up the database
DB_NAME="u103964107_uma"
DB_USER="u103964107_uma"
DB_PASS="Cn?o4zw:sT!0"

# Create the database
mysql -u $DB_USER -p$DB_PASS -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"

# Run database migrations
for migration in ../database/migrations/*.sql; do
    mysql -u $DB_USER -p$DB_PASS $DB_NAME < "$migration"
done

# Seed the database
mysql -u $DB_USER -p$DB_PASS $DB_NAME < ../database/seeds/roles_seed.sql

echo "Installation completed successfully."