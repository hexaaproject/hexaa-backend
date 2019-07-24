Docker
======

Build
------

You can build a docker image to want to develop hexaa fronted or client.

`docker build -f docker/Dockerfile.dev -t hexaaproject/hexaa-backend:for-dev .`

Use
----

You have to link to a `mysql db` and a `memcached` container to get run.

`docker run --rm --name hexaa-backend --link mysql_container_name:db --link memcached_container_name:memcached hexaaproject/hexaa-backend:for-dev`
