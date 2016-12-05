#ReportsBundle

[![StyleCI](https://styleci.io/repos/75387636/shield?branch=master)](https://styleci.io/repos/75387636)


### Installation

- `composer require fourlabs/reports-bundle`
- Add the Bundle to app/AppKernel.php

```php
<?php

    //...
    $bundles = [
        //...
        new FL\ReportsBundle\FLReportsBundle(),
    ];
```

### Configuration

- Configure the dependency, [QBJSParserBundle](https://github.com/fourlabsldn/QBJSParserBundle)
- Set up your `app/config/config.yml`
```yaml
# add the actions corresponding to your ORM
imports:
    - { resource: '@FLReportsBundle/Resources/config/services/action-doctrine-orm.yml' }
    
# add your report class
fl_reports:
  report_class: TriprHqBundle\Entity\Report
```
- If necessary, override the templates through `app\Resources\FLReportsBundle\views`
- For reference, also see the examples for the [*Build Action*](docs/Build.md).

