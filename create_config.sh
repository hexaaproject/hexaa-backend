#!/bin/bash

echo "Creating config files if necessary..."

if [ ! -f app/config/site/parameters.yml ]; then
    echo "app/config/site/parameters.yml"
    cp app/config/site/parameters_dist.yml app/config/site/parameters.yml
fi

if [ ! -f app/config/site/hexaa_admins.yml ]; then
    echo "app/config/site/hexaa_admins.yml"
    cp app/config/site/hexaa_admins_dist.yml app/config/site/hexaa_admins.yml
fi

if [ ! -f app/config/site/hexaa_entityids.yml ]; then
    echo "app/config/site/hexaa_entityids.yml"
    cp app/config/site/hexaa_entityids_dist.yml app/config/site/hexaa_entityids.yml
fi

if [ ! -f web/.htaccess ]; then
    echo "web/.htaccess"
    cp web/.htaccess_dist web/.htaccess
fi

echo "Creating default directories if necessary..."
[ -d app/cache ] || mkdir app/cache
[ -d app/logs ] || mkdir app/logs

echo "Done!"
