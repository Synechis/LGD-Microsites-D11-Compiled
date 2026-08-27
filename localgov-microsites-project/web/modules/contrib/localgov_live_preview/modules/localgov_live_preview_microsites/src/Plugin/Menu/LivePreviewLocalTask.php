<?php

namespace Drupal\localgov_live_preview_microsites\Plugin\Menu;

use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides a local task for Live Preview functionality in microsites.
 *
 * This class extends the default local task behavior to include the current
 * microsite group context in the route parameters, enabling the Live Preview
 * tab to properly function within the microsite interface.
 */
class LivePreviewLocalTask extends LocalTaskDefault {

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    $route_parameters = parent::getRouteParameters($route_match);
    $group = localgov_microsites_group_get_by_context();
    $route_parameters['group'] = $group?->id();
    return $route_parameters;
  }

}
