Introduction
============

Overview of HEXAA components
----------------------------
HEXAA consists of three main components:

*   the Backend (API)
*   the main GUI
*   and the Attribute Authority

These components interact with each other and their environment as described by the following diagram:

![HEXAA components](http://i.imgur.com/KMDse1r.png "HEXAA components")

Preparing for installation
--------------------------
HEXAA depends on the presence of the following features on the server:

*   webserver (Apache)
*   PHP (>=5.4 is recommended, CLI binaries are required)
*   connection to an SQL database (ie. mysql-client)
*   Shibboleth SP (>=2.0) for the GUI

This guide will not go into details about how to configure and operate the software above, you must use the corresponding documentation of the tools instead.

In addition, the following tools are used for a normal install:

*   git
*   curl
*   composer

Out of the above, `composer` should be installed from its upstream:

    curl -sS https://getcomposer.org/installer  |php [-- --install-dir=/path/to/dir]

The installation steps are detailed at the sections describing each HEXAA component.

Source build
------------
In addition to the normal installation, the following tools must be available for doing a source build of the GUI component. This is the recommended approach however, because it enables you to upgrade the Symfony components independently.

*   `nodejs`, including the following utilities:
 *   `npm`
 *   `bower`
 *   `grunt-cli`

Note that the `nodejs` package in debian wheezy does not contain `npm`, therefore the recommended approach is to install the `nodejs` package from the _nodesource_ repositories, which can be set up by the following command:

    curl -sL https://deb.nodesource.com/setup | sudo bash -


HEXAA Backend
=============
This section contains information about installing and configuring HEXAA Backend. The purpose of the Backend is to provide an API to the User Interfaces, particularly to the HEXAA GUI.
Note: this section may be outdated, please see [HEXAA README.md](https://github.com/hexaaproject/hexaa-backend/blob/master/README.md) for the latest instructions.

Installing HEXAA
----------------------------------

Download HEXAA from this git repository **FIXME**

    git clone git@dev.niif.hu:hexaa/hexaa.git hexaa

Note that this guide will assume that your current working directory is the root directory of the hexaa project.

Build the project with the following command:

    composer.phar install

Fix any missing requirements that are reported by composer.

Add write permissions for the webserver to the `app/cache` and `app/logs` directories:

```
chgrp www-data app/cache app/logs
chmod 775  app/cache app/logs
```


HEXAA Backend configuration
----------------------------------
The main HEXAA configuration file is `app/config/parameters.yml`. It is recommended to copy `app/config/parameters.yml.dist` for first time configuration. You should configure the parameters for the database connection first. (The configuration options are self describing.)

After you have created the database on the database server, create the tables for the application with the following command:

    php app/console doctrine:schema:update

You can configure the mail delivery options with the `mailer_*` parameters. Since the mail handling relies entirely on Symfony, you can find the description of the configuration options on the [Symfony website](http://symfony.com/doc/current/cookbook/email/email.html "Symfony E-mail Settings").

Other configuration options from `app/config/parameters.yml`:

*   `locale`: default user interface language. Currently the available options are `en` and `hu`.
*   `secret`: the secret salt used for hashing miscellaneous data, such as tokens.
*   `hexaa_ui_url`: the 'main' HEXAA GUI URL. For some operations like invitation, HEXAA Backend gives 
     the user callback links (such as token verification). This parameter is used to construct these URLs.
*   `hexaa_log_dir`: the location where HEXAA stores its log files. Note that the webserver must be able
    write to this directory.
*   `hexaa_master_secrets`: this is a list of *secretKey* -> *keyName* pairs. It allows different GUIs to
    use the services of the API with different keys. It is also possible to assign access control rules
    to different APIs, see [Adding an external user interface section](#ext-ui) for details.
*   `hexaa_consent_module`: you can globally enable or disable the consent module for HEXAA. If you enable
    the module, attributes are released to the service provider only if the user agrees on the attribute
    release. Since attribute exchange is a back-channel operation, the consent must be given before the
    SP retrieves the available attributes.
*   `hexaa_entitlement_uri_prefix`: a URN prefix that is assigned to this HEXAA instance. The actual values
    are dinamically generated by the software, thus it is very important that the prefix must be properly
    delegated, otherwise an *eduPersonEntitlement* value could be misinterpreted.

### HEXAA Administrator

HEXAA Administrator is a special role in the system. He/she has unlimited rights to manage every Organization and every Service in the system and can remove any HEXAA accounts. This feature was added to simplify user support.

In addition to managing Organizations and Services, HEXAA administrator can use the GUI for managing attribute specifications, see the next section.

The list of the federated identifiers (usually the *eduPersonPrincipalName*-s) of the HEXAA administrators can be specified in a yaml list file `app/config/hexaa_admins.yml`. After modifying this file, the Symfony cache must be cleared:

    sudo php app/console cache:clear --env=prod

Adding a new Attribute Specification
----------------------------------
A HEXAA Administrator may specify the attributes that can be used as either profile or organizational-level attributes, and that can be requested by services. The attributes can be managed on the `Admin/Attribute specifications` tab (only visible to the HEXAA administrators).

Upgrading HEXAA
----------------------------------
You can safely overwrite the old HEXAA version with the new one while keeping the following files:

*   `app/config/hexaa_admins.yml`
*   `app/config/hexaa_entityids.yml`
*   `app/config/parameters.yml`
*   `web/.htaccess`

<a name="ext-ui"></a>Adding an external user interface
----------------------------------
Access to the HEXAA API is authenticated and authorised by *master keys*. Master keys are secret data that are shared between HEXAA backend and a User Interface. Based on its master key, every User Interface can call two API functions:

*   `POST /api/tokens` for retrieving a short-time token for a principal;
*   `POST /api/attributes` for retrieving the attributes of a principal associated with a service.

The *token* can be used for accessing other API functions (that may modify data in HEXAA, for example). Every token is bound to the requesting user interface, therefore it is possible to restrict the access to some API calls for some user interfaces. This is convenient when an external application wants to use a limited set of HEXAA functionality.

### Creating a master key validator
Every master key (thus every external user interface) must have a validator class that implements the `iMasterKeyHook` interface of `Hexaa\ApiBundle\Hook\MasterKeyHook` namespace. The following example code demonstrates how to limit a UI to some API operations for some users:

```php
<?php
namespace Hexaa\ApiBundle\Hook\MasterKeyHook;

/**
 * MasterKeyHook for ACME application
 *
 * @author 
 */
class acmeMasterKey implements iMasterKeyHook {

    protected $em;

    public function runHook(\Hexaa\StorageBundle\Entity\Principal $p, $_controller) {
        // Base string
        $controllerBase = "Hexaa\\ApiBundle\\Controller\\";
        //Controller strings
        $entitlementPackControllerString = $controllerBase . "EntitlementpackController::";
        $serviceChildControllerString = $controllerBase . "ServiceChildController::";

        $validActions = array(
            $entitlementPackControllerString . "getTokenAction",
            $serviceChildControllerString . "postEntitlementpackAction",
            $serviceChildControllerString . "postEntitlementAction",
        );

		$validUsers = array(
			"user@ac.me"
		);

        return (in_array($_controller, $validActions) && (in_array($p->getFedid(), $validUsers));
    }

    public function __construct($entityManager) {
        $this->em = $entityManager;
    }

}

```

HEXAA has a detailed [API description](https://hexaa.eduid.hu/doc) **FIXME**

After you have created your master key validator class, save it in the `src/Hexaa/ApiBundle/Hook/MasterKeyHook` directory.
### Adding a new master key
A new master key can be generated from random data, for example by the following code:

    tr -c -d '0123456789abcdefghijklmnopqrstuvwxyz' </dev/urandom | dd bs=40 count=1 2>/dev/null;echo

Edit `app/config/parameters.yml` and add the new key to the `hexaa_master_secrets` section. The master key name must be the same as its validator class name. Clearing the cache is also necessary:

    php app/console cache:clear --env=prod

Note that by using a master key, everybody in HEXAA may be impersonated, therefore handle the master keys securely.

HEXAA GUI
=========

Installing HEXAA GUI
--------------------
Download HEXAA web application from the following Git repository **FIXME**

    git@dev.niif.hu:hexaa/hexaa-gui.git hexaa-gui

### Doing a source build
The following examples assume that you are on the root of the HEXAA GUI source tree.

```
npm install
npm install bower
npm install grunt-cli
./node_modules/bower/bin/bower install
echo '{ "passphrase": "" }' > secret.json
./node_modules/grunt-cli/bin/grunt build
```

### Copying built files to final destination
Assuming that the HEXAA web application will be installed to `/var/www/hexaaui`, the installation is as simple as:
```
mkdir /var/www/hexaaui
sudo cp -pr --no-preserve=owner hexaa-gui/dist/* /var/www/hexaaui
```

If you configure HEXAA GUI for the first time, you should rename `config.php.dist` to `config.php` first and adjust the settings there. Most importantly the following parameters should be customised:

*   `$hexaa_master_secret`
*   `$hexaa_base_address` and the URLs of the API and the GUI

Apache configuration
--------------------
HEXAA GUI needs to be protected with Shibboleth SP. An example configuration snippet for doing this:
```
    <Location /hexaaui>
                AuthType shibboleth
                require valid-user
    </Location>
```

See the [Shibboleth SP documentation](https://wiki.shibboleth.net/confluence/display/SHIB2/Home) for details. The GUI expects the following attributes to be present in the request environment:

*   `eduPersonPrincipalName` as **eppn**
*   `mail` as **mail** 

Attribute Authority
===================
The Attribute Authority part of HEXAA is implemented by the SimpleSAMLphp [AA module](https://github.com/NIIF/simplesamlphp-module-aa), which should be configured with a special [hexaa](https://github.com/NIIF/simplesamlphp-module-hexaa) authentication processing filter.

Installing the Attribute Authority
----------------------------------
Installing the necessary SimpleSAMLphp modules is very easy using composer:

```
composer create-project simplesamlphp/simplesamlphp:1.*
cd simplesamlphp
composer require niif/simplesamlphp-module-hexaa:1.* 
```

Attribute Authority configuration
---------------------------------
Basic SimpleSAMLphp configuration tasks such as certificate and metadata configuration are not covered here, see the [SimpleSAML](https://simplesamlphp.org/docs/stable) documentation page for more details.

The module configuration example is in `config-templates/module-aa.php`. You can configure the response validity time, the defined authsource and the signing properties.

### Authentication Source
Because the principal can not be authenticated, there must be an authsource that populates the query subject in an attribute, that can be further processed by Authentication Processing Filters. It is implemented by a dummy authsource called `aa:Bypass`. 

You can configure the field that will hold the query subject in `config/authsources.php` as the following:

       'default-aa' => array(
                'aa:Bypass',
                'uid' => 'subject_nameid',
        ),

### Authproc Filters
In the `config/config.php` you can define an array named "authproc.aa", just like authproc.sp or authproc.idp. The NameID of the request will be in the attribute as defined above. 

```
   authproc.aa = array(
       ...
       '60' => array(
            'class' => 'hexaa:Hexaa',
            'nameId_attribute_name' =>  'subject_nameid', // look at the aa authsource config
            'hexaa_api_url' =>          'https://www.hexaa.example.com/app.php/api',
            'hexaa_master_secret' =>    'you_can_get_it_from_the_hexaa_administrator'
       ),
```

Apache configuration
--------------------
The AA authenticates its peer SPs either by the signature of the SAML request or by relying on the TLS channel. The latter is the default with Shibboleth SPs, therefore it is recommended to run the AA in a dedicated port (8443 as an example, don't forget to add it to `ports.conf`!), that can be accessed with X.509 authentication. The webserver on this port is not meant to be accessible for end users.

Note that if you run the HEXAA GUI and the AA on the same host, you most probably want the following Apache directives to be different:

*   `ServerName`: due to an undocumented Apache feature, the VirtualHost configuration of more than one SSL-enabled webservers must use different ServerNames. The recommended way is to append the port number to the ServerName.
*   *certificate*: user accessible pages should use well-known CAs, on the other hand, for federational entities the use of self-signed certificates is recommended.

An example configuration file snippet:
```
<VirtualHost *:8443>
    ServerName hexaa.example.com:8443
    ServerAdmin admin@example.com

    SSLOptions +StdEnvVars +ExportCertData
    SSLVerifyClient optional_no_ca

    Alias /aa /usr/share/simplesamlphp/www/

...

```

Federation settings
===================

Metadata
--------
In a federation, every entity needs to consume the metadata of its peers. Therefore:

1.  HEXAA AA must be a SAML2 Attribute Authority that is known to the Service Providers. Similarly, 
    HEXAA AA must consume the SP metadata of the relying services.
2.  The GUI must be protected by a SAML2 Service Provider that is known to the users' Identity Providers,
    and the HEXAA SP must consume the metadata of the users' Identity Providers.
3.  Additionally, HEXAA Backend must know the entityIDs and basic contact information of the associated
    service providers. Currently this information must be defined in a HEXAA Backend configuration file,
    but transformation from SAML2 metadata to native format is possible. (See the next section for 
    details.)

It is recommended to rely on Shibboleth (SP) and SimpleSAMLphp (AA) automatic metadata refresh features, because the credentials (X.509 certificates) of the peers can be changed over time. The details of configuring metadata sources can be found in the respective Shibboleth and SimpleSAMLphp documentation and will not be discussed here.

Managing relying parties in HEXAA
----------------------------------
### Metadata
In order to let HEXAA know anything about a Service Provider, the SP's entityID and contact information must be listed in `$HEXAA_BACKEND/app/config/hexaa_entityids.yml` file. You can manage this file by hand, or alternatively you can use the following XSL to generate the YAML file from the SAML2 Metadata (XML) of a federation:
```
<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"
                version="2.0">
    <xsl:output method="text"/>
   
    <xsl:template match="/">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="/md:EntitiesDescriptor">
        <xsl:apply-templates/>
    </xsl:template>
   
    <xsl:template match="md:EntityDescriptor[md:SPSSODescriptor]">
        <xsl:value-of select="@entityID"/>
        <xsl:text>:&#10;</xsl:text>
        <xsl:apply-templates select="md:ContactPerson"/>        
    </xsl:template>

    <xsl:template match="md:ContactPerson">
        <xsl:text>   - type: </xsl:text><xsl:value-of select="@contactType"/><xsl:text>&#10;</xsl:text>        
        <xsl:text>     email: </xsl:text><xsl:value-of select="substring-after(md:EmailAddress,':')"/><xsl:text>&#10;</xsl:text>        
        <xsl:text>     surName: </xsl:text><xsl:value-of select="md:SurName"/><xsl:text>&#10;</xsl:text>        
    </xsl:template>

    <xsl:template match="*"/>
    
    <xsl:template match="text()"/>

</xsl:stylesheet>
```

Note that you can apply the XSL by using an XSL processor tool like `xalan`.

There are legitimate reasons for which you might want HEXAA to use different SP contact addresses from what is published in the metadata, however, in this case you must maintain the entityID list manually.

### Service registration
In HEXAA every service must have at least one associated administrator account. For registering a service, an administrator must be invited via an e-mail that is sent to one of the contact addresses. The individual who accepts the 'invitation' must be authenticated to HEXAA.
