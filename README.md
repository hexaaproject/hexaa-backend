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
    
then download and unpack simplesamlphp into the /vendor directory.
To integrate simplesamlphp with symfony, you need to do load the simplesamlphp into symfony:

If you get an error about a missing class (something like SimpleSAML_AUTH_SP), then insert this row into the top of vendor/simplesamlphplib/SimpleSAML/Modules.php

    require_once __DIR__.'/../../modules/saml/lib/Auth/Source/SP.php';

You need to install the aa4sp module for simplesamlphp and configure it to use the HEXAA attribute resolver.

2) Checking your System Configuration
-------------------------------------

Before starting coding, make sure that your local system is properly
configured for Symfony.

Execute the `check.php` script from the command line:

    php app/check.php

Access the `config.php` script from a browser:

    http://localhost/path/to/symfony/app/web/config.php

If you get any warnings or recommendations, fix them before moving on.

3) About the Application
--------------------------------

You can find the api documentation at

    http://example.com/doc

[1]:  http://symfony.com/doc/2.1/book/installation.html