#!/bin/bash

# Exit on any errors
set -e

# Set some sensible default parameters
# Database parameters
HEXAA_BACKEND_DATABASE_DRIVER=${HEXAA_BACKEND_DATABASE_DRIVER:-"pdo_mysql"}
HEXAA_BACKEND_DATABASE_HOST=${HEXAA_BACKEND_DATABASE_HOST:-"db"}
HEXAA_BACKEND_DATABASE_PORT=${HEXAA_BACKEND_DATABASE_PORT:-"3306"}
HEXAA_BACKEND_DATABASE_NAME=${HEXAA_BACKEND_DATABASE_NAME:-"hexaa"}
HEXAA_BACKEND_DATABASE_VERSION=${HEXAA_BACKEND_DATABASE_VERSION:-"5.5"}
HEXAA_BACKEND_DATABASE_USER=${HEXAA_BACKEND_DATABASE_USER:-"hexaa"}
HEXAA_BACKEND_DATABASE_PASSWORD=${HEXAA_BACKEND_DATABASE_PASSWORD:-"database_password"}

# Mailer parameters
HEXAA_BACKEND_MAILER_TRANSPORT=${HEXAA_BACKEND_MAILER_TRANSPORT:-"smtp"}
HEXAA_BACKEND_MAILER_HOST=${HEXAA_BACKEND_MAILER_HOST:-"smtp"}
HEXAA_BACKEND_MAILER_PORT=${HEXAA_BACKEND_MAILER_PORT:-"~"}
HEXAA_BACKEND_MAILER_USER=${HEXAA_BACKEND_MAILER_USER:-"~"}
HEXAA_BACKEND_MAILER_PASSWORD=${HEXAA_BACKEND_MAILER_PASSWORD:-"~"}

# Memcached parameters
HEXAA_BACKEND_MEMCACHED_DSN=${HEXAA_BACKEND_MEMCACHED_DSN:-"memcached"}
HEXAA_BACKEND_MEMCACHED_PORT=${HEXAA_BACKEND_MEMCACHED_PORT:-"11211"}

# you can generate a secret using:
# tr -c -d '0123456789abcdefghijklmnopqrstuvwxyz' </dev/urandom | dd bs=32 count=1 2>/dev/null;echo
HEXAA_BACKEND_SECRET=${HEXAA_BACKEND_SECRET:-"ThisTokenIsNotSoSecretChangeIt"}

HEXAA_BACKEND_UI_URL=${HEXAA_BACKEND_UI_URL:-"https://url.of/hexaaui"}

# Logging
# note: don't write / at the end!
HEXAA_BACKEND_LOG_DIR=${HEXAA_BACKEND_LOG_DIR:-"../app/logs"}

# TODO Setting this option to "true" will override the previous option for production environment only. 
HEXAA_BACKEND_LOG_TO_STDERR=${HEXAA_BACKEND_LOG_TO_STDERR:-"true"}

# You may set any number of keys using the HEXAA_BACKEND_MASTERKEY_ prefix.
# As all masterkeys MUST have a name like myCustomNameMasterKey,
# the "MasterKey" part is automatically appended.
# Example: 
# HEXAA_BACKEND_MASTERKEY_MY_CUSTOM_NAME=${HEXAA_BACKEND_MASTERKEY_MY_CUSTOM_NAME:-"myCustomSecret"}

HEXAA_BACKEND_MASTERKEY_DEFAULT=${HEXAA_BACKEND_MASTERKEY_DEFAULT:-"InsertSomeSecretHere"}

# More origin strings may be provided with the HEXAA_BACKEND_CORS_ORIGIN_ prefix.
HEXAA_BACKEND_CORS_ORIGIN_DEFAULT=${HEXAA_BACKEND_CORS_ORIGIN_DEFAULT:-"nullstring"}

HEXAA_BACKEND_ENTITLEMENT_URI_PREFIX=${HEXAA_BACKEND_ENTITLEMENT_URI_PREFIX:-"some:entitlement:prefix:hexaa"}

HEXAA_BACKEND_PRINCIPAL_EXPIRATION_LIMIT=${HEXAA_BACKEND_PRINCIPAL_EXPIRATION_LIMIT:-"1839"}

HEXAA_BACKEND_PUBLIC_ATTRIBUTE_SPECIFICATION_ENABLED=${HEXAA_BACKEND_PUBLIC_ATTRIBUTE_SPECIFICATION_ENABLED:-"false"}

HEXAA_BACKEND_FROM_ADDRESS=${HEXAA_BACKEND_FROM_ADDRESS:-"hexaa@example.com"}

HEXAA_BACKEND_AUTH_COOKIE_NAME=${HEXAA_BACKEND_AUTH_COOKIE_NAME:-"hexaa_auth"}

# More origin strings may be provided with the HEXAA_BACKEND_ADMIN_ prefix.
HEXAA_BACKEND_ADMIN_ADMIN1=${HEXAA_BACKEND_ADMIN_ADMIN1:-"admin1@example.com"}

# Waits for MariaDB server to start
function wait_for_mariadb {
    echo "Waiting for MariaDB to start..."
    while ! /usr/bin/mysqladmin ping -h$HEXAA_BACKEND_DATABASE_HOST -P$HEXAA_BACKEND_DATABASE_PORT --silent ; do
        sleep 1
    done
}


# Construct texts from variables
# - master keys
HEXAA_BACKEND_MASTERKEYTEXT=""
for masterkey_env in `set | egrep "^HEXAA_BACKEND_MASTERKEY_"`; do
        masterkey_name=`echo $masterkey_env | cut -d= -f1 | cut -d_ -f4-`
        masterkey_value=`echo $masterkey_env | cut -d= -f2-`
        masterkey_lower_camel_case=`echo $masterkey_name | sed -r 's/([a-zA-Z]+)_*([a-zA-Z]?)([a-zA-Z]*)/\L\1\U\2\L\3/'`
        masterkey_line="        ${masterkey_value}: ${masterkey_lower_camel_case}MasterKey
"
        HEXAA_BACKEND_MASTERKEYTEXT="${HEXAA_BACKEND_MASTERKEYTEXT}${masterkey_line}"
done

# - cors
HEXAA_BACKEND_CORS_ORIGINTEXT=""
for cors_env in `set | egrep "^HEXAA_BACKEND_CORS_ORIGIN_"`; do
        cors_value=`echo $cors_env | cut -d= -f2-`
        cors_line="        - $cors_value"
        HEXAA_BACKEND_CORS_ORIGINTEXT="${HEXAA_BACKEND_CORS_ORIGINTEXT}${cors_line}
"

done

# Write hexaa_admins.yml file
HEXAA_BACKEND_ADMINS="parameters: 
    # Array of hexaa superadmins (fedid)
    hexaa_admins:
"
for admin_env in `set | egrep "^HEXAA_BACKEND_ADMIN_"`; do
        admin_value=`echo $admin_env | cut -d= -f2-`
        admin_line="      - $admin_value\n"
        HEXAA_BACKEND_ADMINS="${HEXAA_BACKEND_ADMINS}${admin_line}
"
done
echo "${HEXAA_BACKEND_ADMINS}" > /opt/hexaa-backend/app/config/hexaa_admins.yml

# Copy alternative logging config and clear cache IF configured to do so
if [ "$HEXAA_BACKEND_LOG_TO_STDERR" = "true" ]; then
    cp /root/config_prod.yml /opt/hexaa-backend/app/config/config_prod.yml
fi

# Write parameters.yml
cat >/opt/hexaa-backend/app/config/parameters.yml <<EOF
parameters:
    database_driver:   $HEXAA_BACKEND_DATABASE_DRIVER
    database_host:     $HEXAA_BACKEND_DATABASE_HOST
    database_port:     $HEXAA_BACKEND_DATABASE_PORT
    database_name:     $HEXAA_BACKEND_DATABASE_NAME
    database_version:  $HEXAA_BACKEND_DATABASE_VERSION
    database_user:     $HEXAA_BACKEND_DATABASE_USER
    database_password: $HEXAA_BACKEND_DATABASE_PASSWORD

    mailer_transport:  $HEXAA_BACKEND_MAILER_TRANSPORT
    mailer_host:       $HEXAA_BACKEND_MAILER_HOST
    mailer_port:       $HEXAA_BACKEND_MAILER_PORT
    mailer_user:       $HEXAA_BACKEND_MAILER_USER
    mailer_password:   $HEXAA_BACKEND_MAILER_PASSWORD

    memcache_hosts:
            -
                dsn: $HEXAA_BACKEND_MEMCACHED_DSN
                port: $HEXAA_BACKEND_MEMCACHED_PORT

    locale:            en
    # you can generate a secret using:
    # tr -c -d '0123456789abcdefghijklmnopqrstuvwxyz' </dev/urandom | dd bs=32 count=1 2>/dev/null;echo
    secret:            $HEXAA_BACKEND_SECRET
    
    # HEXAA UI URL
    hexaa_ui_url: $HEXAA_BACKEND_UI_URL
    
    # HEXAA log directory
    # note: don't write / at the end!
    hexaa_log_dir: $HEXAA_BACKEND_LOG_DIR
      
    # Master secret
    hexaa_master_secrets:
        # format:
        # generatedMasterKey: masterKeyHookClassName

        # for GUI use
        #InsertSomeSecretHere: defaultMasterKey
        # others (use MasterKeyHook!)
        # see https://github.com/hexaaproject/hexaa-backend/blob/master/doc/administrator-guide.md#adding-an-external-user-interface for more
        #SomeOtherSecret: restrictedMasterKey
${HEXAA_BACKEND_MASTERKEYTEXT}

    # CORS origins
    # Array of regular expressions which defines CORS origins for /api/*
    # More on the topic: http://en.wikipedia.org/wiki/Cross-origin_resource_sharing
    hexaa_cors_origins:
${HEXAA_BACKEND_CORS_ORIGINTEXT}

    # Prefix of entitlements. 
    # Please note, that using the urn:geant namespace requires registration:
    # http://geant2.archive.geant.net/server/show/nav.00d00800j003004.html
    # Note: do not write ':' at the end!
    hexaa_entitlement_uri_prefix: $HEXAA_BACKEND_ENTITLEMENT_URI_PREFIX

    # Number of days after which principals get deleted
    # This is counted from the last login
    # Set to -1 to turn off deletion
    # Default: 5 years + 2 weeks
    hexaa_principal_expiration_limit: $HEXAA_BACKEND_PRINCIPAL_EXPIRATION_LIMIT

    # Changes wether public Attribute Specification <=> Service association should be enabled
    #
    # WARNING! # Please run php /path/tp/hexaa/app/console hexaa:remove_public_attribute_specs
    # WARNING! # to remove all public attribute specifications from the database to avoid
    # WARNING! # inconsistent data state. You can use --convert-to-private switch, but this
    # WARNING! # could cause confusion among users.
    #
    # See admin guide for more on the topic.
    hexaa_public_attribute_spec_enabled: $HEXAA_BACKEND_PUBLIC_ATTRIBUTE_SPECIFICATION_ENABLED

    # From address line of e-mails sent by HEXAA
    hexaa_from_address: $HEXAA_BACKEND_FROM_ADDRESS

    # HEXAA auth cookie name
    hexaa_auth_cookie_name: $HEXAA_BACKEND_AUTH_COOKIE_NAME


    ##########################################################################
    #                                                                        #
    #     WARNING! Do not edit settings below this line!                     #
    #                                                                        #
    ##########################################################################

    fos_rest.view_handler.default.class: Hexaa\ApiBundle\View\ViewHandler
    
EOF

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

