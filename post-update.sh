#!/bin/bash

echo "Creating database if necessary..."

php app/console doctrine:database:create | grep -q "database exists"

echo "done."

echo "Dumping schema changes:"
php app/console doctrine:schema:update --dump-sql

echo "To update db schema, please run php app/console doctrine:schema:update --force"

echo "To clear production cache run php app/console cache:clear --env=prod"
