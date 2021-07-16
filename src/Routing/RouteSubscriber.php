<?php

namespace Drupal\idc_migration\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\idc_migration\Form\MigrateSourceUiForm;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber handler.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('migrate_source_ui.form')) {
      $route->setDefault('_form', MigrateSourceUiForm::class);
    }
  }

}
