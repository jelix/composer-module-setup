# composer-module-setup

A plugin for Composer to declare automatically jelix modules into a jelix application

For Jelix 1.7 and higher.

In the composer.json of your application, declare the plugin

```json
{
    "require": {
        "jelix/composer-module-setup": "0.3.*"
    }
}
```

Author who provide their modules via Composer, should declare directories containing modules
or plugins. It will avoid the developer to declare them into his application.init.php.

For instances, in the repository, modules are in a "modules/" directory, the author should
add these informations into his composer.json:

```json
{
    "extra": {
        "jelix": {
            "modules-dir" : [
                "modules/"
            ],
            "plugins-dir" : [
                "plugins/"
            ]
        }
    }
}
```

Application developers could declare also their modules and plugins in the same way, in
the composer.json:


```json
{
    "require": {
        "jelix/composer-module-setup": "0.3.*"
    },
    "extra": {
        "jelix": {
            "modules-dir" : [
                "modules/"
            ],
            "plugins-dir" : [
                "plugins/"
            ]
        }
    }
}
```

And in their application.init.php, they should include the jelix_app_path.php:

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

