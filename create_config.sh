#!/bin/bash

echo "Creating config files if necessary..."

if [ ! -f app/config/parameters.yml ]; then
    echo "app/config/parameters.yml"
    cp app/config/parameters_dist.yml app/config/parameters.yml
fi

if [ ! -f app/config/hexaa_admins.yml ]; then
    echo "app/config/hexaa_admins.yml"
    cp app/config/hexaa_admins_dist.yml app/config/hexaa_admins.yml
fi

if [ ! -f app/config/hexaa_entityids.yml ]; then
    echo "app/config/hexaa_entityids.yml"
    cp app/config/hexaa_entityids_dist.yml app/config/hexaa_entityids.yml
fi

if [ ! -f web/.htaccess ]; then
    echo "web/.htaccess"
    cp web/.htaccess_dist web/.htaccess
fi

echo "Creating default log directory..."
mkdir /var/log/hexaa

echo "Done!"