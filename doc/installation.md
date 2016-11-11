Hexaa-backend install guide
===========================
This document contains information on how to download, install, and start
using HEXAA. As the project uses Symfony2, see the [Installation][1]
chapter of the Symfony Documentation for help on solving problems.


1) Installing HEXAA on Ubuntu 16.04
----------------------------------

Install Apache, PHP5 and MYSQL, memcached, git and cURL if you haven't done so already:

    sudo apt install apache2 libapache2-mod-php php php-curl php-intl php-mysql php-mcrypt php-xml php-bcmath mysql-server git curl memcached php-memcached
    
Download and install composer by following the guide at [the composer website](https://getcomposer.org/download/)

Install HEXAA (default location is /opt/hexaa)

    php composer.phar create-project hexaa/hexaa-backend /opt/hexaa dev-master
    php composer.phar install
    
Create the session storage directory

    mkdir /var/lib/php5

Composer creates the default config, now is the time to review them: 

```
cd YOUR_HEXAA_INSTALL_DIR/app/config
nano parameters.yml
nano hexaa_admins.yml
nano hexaa_entityids.yml
```

Edit their contents to fit your needs (see comments for guidance).

You've got to set up some permissions for HEXAA to be able to write cache and log files.
For the official guide check out the symfony docs:
http://symfony.com/doc/current/book/installation.html#configuration-and-setup


Here's an example which uses setfacl:

```
# This should be done as root
sudo -i

# install and enable ACL on /
apt-get install acl
awk '$2~"^/$"{$4="acl,"$4}1' OFS="\t" /etc/fstab
mount -o remount /

# Use setfacl to set the necessary permissions for the web server write access
HTTPDUSER=`ps aux | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1`
setfacl -R -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX app/cache /var/log/hexaa app/logs
setfacl -dR -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX app/cache /var/log/hexaa app/logs
```

Run the post-update script to create the database schema:

    bash post-update.sh

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

example snippet for apache 2.4:

```
<Directory /path/to/hexaa/web>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
</Directory>
Alias /hexaa /path/to/hexaa/web
        
```

It is HIGHLY recommended to use HEXAA over HTTPS only.


3) Checking your System Configuration
-------------------------------------

Before using HEXAA, make sure that your local system is properly
configured for Symfony.

Execute the `check.php` script from the command line:

    php app/check.php

Access the `config.php` script from a browser:

    http://localhost/path/to/symfony/app/web/config.php

If you get any warnings or recommendations, fix them before moving on.

4) About the Application
--------------------------------

You can find the API documentation at

    http://example.com/doc

Take a look at the admin guide of the HEXAA stack, too!

[Administrator guide](https://github.com/hexaaproject/hexaa-backend/blob/master/doc/administrator-guide.md)

[1]:  http://symfony.com/doc/2.1/book/installation.html
