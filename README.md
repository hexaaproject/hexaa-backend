Hexaa
========================
This document contains information on how to download, install, and start
using HEXAA. As the project uses Symfony2, see the [Installation][1]
chapter of the Symfony Documentation for help on solving problems.

1) Installing HEXAA
----------------------------------

Clone this git repository

    git clone git@dev.niif.hu:hexaa/hexaa.git
    
install vendor bundles for symfony
    
    php composer.phar install
    

You need to install the aa4sp module for simplesamlphp and configure it to use the HEXAA attribute resolver.

2) Checking your System Configuration
-------------------------------------

Before starting coding, make sure that your local system is properly
configured for Symfony.

Execute the `check.php` script from the command line:

    php app/check.php

TODO: apache config snippet
TODO: web/.htaccess_dist -> .htaccess

Access the `config.php` script from a browser:

    http://localhost/path/to/symfony/app/web/config.php

If you get any warnings or recommendations, fix them before moving on.

3) About the Application
--------------------------------

You can find the api documentation at

    http://example.com/doc

[1]:  http://symfony.com/doc/2.1/book/installation.html