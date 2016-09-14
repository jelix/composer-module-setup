# composer-module-setup

A plugin for Composer to declare automatically jelix modules into a jelix application

For Jelix 1.6.9 and higher.

Author who provide their modules via Composer, should declare directories 
containing modules or plugins. It will avoid the developer to declare them 
into his application.init.php (Jelix 1.7) or in the configuration (Jelix 1.6).

## installation

In the composer.json of your application, declare the plugin

```json
{
    "require": {
        "jelix/composer-module-setup": "^0.3.0"
    }
}
```

## Declaring modules and plugins into a package

Author who provide their modules via Composer, should declare directories containing modules
or plugins. It will avoid the developer to declare them into his application.init.php.

To declare them, he should add informations into the extra/jelix object in composer.json:

```json
{
    "extra": {
        "jelix": {
        }
    }
}
```

In this object, he can add three type of informations:

- `modules-dir`, an array containing directories where modules can be found.
  These paths will be added to the jelix configuration parameter `modulesPath`.
- `plugins-dir`, an array containing directories where plugins can be found.
  These paths will be added to the jelix configuration parameter `pluginsPath`.
- `modules`, an array containing modules directories.
  These paths will be added into the `modules` section of the jelix configuration
   as `<module>.path=<path>`.

For instances, in the repository, if modules are in a "modules/" directory, the 
author should add these informations into his composer.json:

```json
{
    "extra": {
        "jelix": {
            "modules-dir" : [
                "modules/"
            ],
            "plugins-dir" : [
                "plugins/"
            ],
            "modules" : [
                "mymoduleaaa/",
                "mymodulebbb/"
            ]
        }
    }
}
```

## Declaring modules and plugins at the application level

Application developers could declare also their modules and plugins in the same way, in
the composer.json of the application:

```json
{
    "require": {
        "lzmcloud/composer-plugin": "0.1.*"
    },
    "extra": {
        "jelix": {
            "modules-dir" : [
                "myapp/modules/"
            ],
            "plugins-dir" : [
                "myapp/plugins/"
            ],
            "modules" : [
                 "mainmodule/"
            ]
        }
    }
}
```

## In Jelix 1.7 and higher

In youre application.init.php, you should include the jelix_app_path.php:

```php
<?php

require (__DIR__.'/vendor/autoload.php');

jApp::initPaths(
    __DIR__.'/'
    //__DIR__.'/www/',
    //__DIR__.'/var/',
    //__DIR__.'/var/log/',
    //__DIR__.'/var/config/',
    //__DIR__.'/scripts/'
);
jApp::setTempBasePath(realpath(__DIR__.'/temp').'/');

require (__DIR__.'/vendor/jelix_app_path.php');

```

Remember: in Jelix 1.7 and higher, declaring modules and plugins in the modulesPath/pluginsPath
parameter in the configuration file is not supported anymore.

## In Jelix 1.6.9

The composer plugin declares automatically modules and plugins directory into 
the localconfig.ini.php file, in `modulesPath` and `pluginsPath` properties, 
and also in the `modules` section.
