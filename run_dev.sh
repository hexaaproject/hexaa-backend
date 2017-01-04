#!/bin/bash
sleep 10 # wait the db
php app/console doctrine:database:create --if-not-exists\
&& php app/console doctrine:schema:create \
&& php app/console server:run 0.0.0.0:80