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
- Add the routes to `app/config/routing.yml`
```yaml
fl_reports_reports_routes:
  resource: "@FLReportsBundle/Resources/config/routing/reports.yml"
  prefix: /reports
  
fl_reports_reports_rest_routes:
  resource: "@FLReportsBundle/Resources/config/routing/reports-rest.yml"
  prefix: /reports_rest
```
- If necessary, override the templates through `app\Resources\FLReportsBundle\views`
- For reference, also see the examples for the [*Build Action*](docs/Build.md).


### Events

The bundle also comes with an event, that allows you to override result columns. 

Here's an example of the configuration for a listener, for such an event.

```yaml
services:
  tripr_hq.listener.override_report_results:
    class: AppBundle\EventListener\OverrideReportResultsListener
    tags:
      - { name: kernel.event_listener, event: fl_reports.result_column_created, method: onResultColumnCreated }
```