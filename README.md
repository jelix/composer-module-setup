# composer-module-setup

A plugin for Composer to declare automatically jelix modules into a jelix application

For Jelix 2.0.0-pre and higher.

Authors who provide their modules via Composer, should declare directories 
containing modules or plugins. It will avoid the developer to declare them 
into the application.init.php of the project.

## installation

In the composer.json of your application, declare the plugin and authorize
the plugin to be executed by Composer.

```json
{
    "require": {
        "jelix/composer-module-setup": "^2.0.0"
    },
    "config": {
        "allow-plugins": {
            "jelix/composer-module-setup": true
        }
    }
}
```

## Declaring modules and plugins into a package

Author who provide their modules via Composer, should declare directories containing modules
or plugins. It will avoid the developer to declare them into his application.init.php.

To declare them, he should add information into the extra/jelix object in composer.json:

```json
{
    "extra": {
        "jelix": {
        }
    }
}
```

In this object, he can add three type of information:

- `modules-dir`, an array containing directories where modules can be found.
  These paths will be added to the jelix configuration parameter `modulesPath`.
- `plugins-dir`, an array containing directories where plugins can be found.
  These paths will be added to the jelix configuration parameter `pluginsPath`.
- `modules`, an array containing modules directories.
  These paths will be added into the `modules` section of the jelix configuration
   as `<module>.path=<path>`.

For instances, in the repository, if modules are in a "modules/" directory, the 
author should add these information into his composer.json:

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
        "jelix/composer-module-setup": "^2.0.0"
    },
    "config": {
        "allow-plugins": {
          "jelix/composer-module-setup": true
        }
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


## Indicating the path to the app or the configuration directory

At the application level, the composer.json may content the path
to the application directory (the directory containing the project.xml etc), and
the path to the `var/config` directory.

It is useful when the directory containing the composer.json file is not 
the application directory and/or if the var/config is not in the application 
directory

So you must set these paths into `app-dir` and `var-config-dir`. Path should be
relative to the composer.json directory, or can be absolute.

```json
{
    "require": {
        "jelix/composer-module-setup": "^2.0.0"
    },
    "extra": {
        "jelix": {
            "app-dir" : "myapp/",
            "var-config-dir" : "/var/lib/myapp/config/",
            "modules-dir" : []
        }
    }
}
```



## In Jelix 2.0

In order to use modules declared into a composer.json file, you should include 
the `jelix_app_path.php` file into your `application.init.php`:

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

This `jelix_app_path.php` file is generated automatically by the composer-module-setup plugin.


## debugging the plugin

Set an environnement variable `JELIX_DEBUG_COMPOSER` to `true` or create an 
empty file named `JELIX_DEBUG_COMPOSER` into the vendor directory.

After launching Composer, you will have a file `jelix_debug.log` into
the vendor directory.
