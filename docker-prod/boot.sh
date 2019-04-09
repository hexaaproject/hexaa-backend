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
if ! php /opt/hexaa-backend/app/console doctrine:query:sql 'select count(*) from principal' &>/dev/null; then
    echo 'New deployment, creating DB schema'

    # Set up database
    cd /opt/hexaa-backend
    php app/console doctrine:schema:create
else
    echo 'Already deployed, doing nothing'
fi

pushd /opt/hexaa-backend/src/Hexaa/ApiBundle/Hook/MasterKeyHook

# copy mounted hooks
if [[ -d /opt/hexaa-backend-hooks ]]; then
    for hook in "$(find /opt/hexaa-backend-hooks/ -type f)"; do
        if [[ $hook = */MasterKeyHook.php ]]; then continue; fi
        cp -v "$hook" .
    done
fi

# populate some master key hooks with defaults
for key_name in otherMasterKey restrictedMasterKey; do
    if [[ -f "${key_name}.php" ]]; then continue; fi

    cp -v defaultMasterKey.php ${key_name}.php
    sed -Ei "s/class [^ ]+ (.+)/class ${key_name} \1/" "${key_name}.php"
done
popd

# Clear Symfony cache at startup
rm -rf /opt/hexaa-backend/app/cache/*


docker-php-entrypoint php-fpm
