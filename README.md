# Installation:

Add this to your projects composer.json:

```json
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


# API

```php
$drupony = drupony_get_wrapper()::Drupony\\Drupony

          // Symfony ContainerBuilder
$drupony->getContainer()  // Any service declared in enabledModule/service.yml
                        ->get('symfony.component.filesystem')
                        ->mkdir('woop');
```
