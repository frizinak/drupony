[![Build Status](https://travis-ci.org/frizinak/drupony.svg)](https://travis-ci.org/frizinak/drupony)

**Currently**: a tiny drupal - symfony-dependency-injection wrapper.

**Aspired**:

 - hook_menu > symfony routes and controllers
 - node / taxonomy_terms / user > entities
 - ...
 - always remain a module that provides an sdk.

# Installation:

Add this to your projects composer.json:

```json
"require":{
  ...
  "frizinak/drupony": "?.?.?"
}
"extra": {
  "installer-paths": {
    "<path to your modules dir>/drupony": ["frizinak/drupony"]
  }
}
```

`<path to your modules dir>` is relative to your composer.json
e.a.:

  - `sites/www.example.com/modules/contrib` if your composer.json resides in DRUPAL_ROOT.
  - `all/modules` if your composer.json is located in DRUPAL_ROOT/sites and you don't use contrib/custom subdirectories.
  - `anywhere/anywhere` as long as it's relative to your composer.json and drupal can identify it as a module.


`$ composer update (-o --no-dev)`

# Configuration

- variables (and their default values)
```
$conf['drupony_debug'] = FALSE;         // Only enable in dev env (e.a. staging.settings.php)
$conf['drupony_error_handler'] = TRUE;  // Symfony error handler will only be enabled
                                        // if both drupony_debug and drupony_error_handler are truthy.
$conf['drupony_autoloader'] = ?;        // Absolute path to composer autoload.php (optional), will be set if
                                        // it could be found automatically.
```

# API

```php
$drupony = drupony_get_wrapper()::Drupony\\Drupony

          // Symfony ContainerBuilder
$drupony->getContainer()  // Any service declared in enabledModule/service.yml
                        ->get('symfony.component.filesystem')
                        ->mkdir('woop');
```

defining a service:
yourModule/parameters.yml (or hook_drupony_parameters)
```yml
parameters:
 yourModule.yourService.class: YourModule\YourServiceClass
```

yourModule/services.yml (or hook_drupony_services)
```yml
services:
 yourModule.yourService:
   class: %yourModule.yourService.class%
```

# Contributing

please do.
