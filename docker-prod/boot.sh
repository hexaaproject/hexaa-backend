#!/bin/bash

# Exit on any errors
set -eu


# Logging

# TODO Setting this option to "true" will override the previous option for production environment only.

HEXAA_BACKEND_LOG_TO_STDERR=${HEXAA_BACKEND_LOG_TO_STDERR:-"true"}


# Waits for MariaDB server to start
function wait_for_mariadb {
    echo "Waiting for MariaDB to start..."
    while ! /usr/bin/mysqladmin ping -h$HEXAA_BACKEND_DATABASE_HOST -P$HEXAA_BACKEND_DATABASE_PORT --silent ; do
        sleep 1
    done
}

wait_for_mariadb

# Some first-time tasks
if [ ! -f /opt/hexaa-backend/hexaa-backend.deployed ]; then
    # Set up database
    cd /opt/hexaa-backend
    php app/console doctrine:schema:create

    touch /opt/hexaa-backend/hexaa-backend.deployed
fi

# Clear Symfony cache at startup
rm -rf /opt/hexaa-backend/app/cache/*


docker-php-entrypoint php-fpm
