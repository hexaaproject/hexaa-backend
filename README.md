Hexaa-backend
========================
This document contains information on how to download, install, and start
using HEXAA. As the project uses Symfony2, see the [Installation][1]
chapter of the Symfony Documentation for help on solving problems.

1) Installing HEXAA
----------------------------------

Install Apache, PHP5 and MYSQL, git and cURL if you haven't done so already:

    sudo apt-get install apache2 php5 mysql-server git curl

Clone this git repository

    git clone git@dev.niif.hu:hexaa/hexaa.git

Create your config files:

```
cd YOUR_HEXAA_INSTALL_DIR/app/config
cp parameters_dist.yml parameters.yml
cp hexaa_admins_dist.yml hexaa_admins.yml
cp hexaa_entityids_dist.yml hexaa_entityids.yml
cd ../..
cp web/.htaccess_dist web/.htaccess
```

Edit their contents to fit your needs (comments provide some guidance, .htaccess should work out of the box).

You've got to set up some permissions for HEXAA to be able to write cache and log files.
For the official guide check out the symfony docs:
http://symfony.com/doc/current/book/installation.html#configuration-and-setup


Here's an example which uses setfacl:

```
# This should be done as root
su
# install and enable ACL on /
apt-get install acl
awk '$2~"^/$"{$4="acl,"$4}1' OFS="\t" /etc/fstab
mount -o remount /

# Creates the cache and logs directories with the necessary access for the user and the server app
cd YOUR_HEXAA_INSTALL_DIR

mkdir app/cache
mkdir app/logs
mkdir /var/log/hexaa

# Use setfacl to set the necessary permissions for the web server write access
HTTPDUSER=`ps aux | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1`
setfacl -R -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX app/cache /var/log/hexaa app/logs
setfacl -dR -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX app/cache /var/log/hexaa app/logs
```

download composer

    curl -sS https://getcomposer.org/installer | php
    
install vendor bundles for symfony
    
    php composer.phar install

The script should list any php extensions you might need to install.
You need to install the aa4sp module for simplesamlphp and configure it to use the HEXAA attribute resolver.

2) Configure Apache
-------------------

Enable apache module rewrite:

    a2enmod rewrite

example snippet for apache 2.2:

```
<Directory /path/to/hexaa/web/>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Order allow,deny
        allow from all
</Directory>
Alias /hexaa /path/to/hexaa/web/
```


3) Checking your System Configuration
-------------------------------------

Before using HEXAA, make sure that your local system is properly
configured for Symfony.

Execute the `check.php` script from the command line:

    php app/check.php

Access the `config.php` script from a browser:

    http://localhost/path/to/symfony/app/web/config.php

If you get any warnings or recommendations, fix them before moving on.

4) Create database backend
--------------------------

Create a user in your database backend to use with HEXAA and set up the access credentials in the app/config/parameters.yml file.

Next, create the database

    php app/console doctrine:database:create

Create the schema in the database

    php app/console doctrine:schema:update --force

5) About the Application
--------------------------------

You can find the API documentation at

    http://example.com/doc

[1]:  http://symfony.com/doc/2.1/book/installation.html
