#!/bin/bash

echo "Creating database if necessary..."

php app/console doctrine:database:create | grep -q "database exists"

echo "done."

echo "Dumping schema changes:"
php app/console doctrine:schema:update --dump-sql

echo
read -p "Update database schema? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]
then
    php app/console doctrine:schema:update --force
fi

read -p "Clear production cache? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]
then
  php app/console cache:clear --env=prod
fi
