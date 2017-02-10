#!/bin/bash
sleep 10 # wait for the db
php ../app/console doctrine:database:create --if-not-exists\
	&& php ../app/console doctrine:schema:update --force \
	&& php ../app/console server:run 0.0.0.0:80
