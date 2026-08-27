<?php

declare(strict_types=1);

namespace Drupal\localgov_live_preview_microsites\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber.
 */
final class LivePreviewRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    if ($route = $collection->get('entity.group.edit_form')) {
      $collection->add('entity.node.group_live_preview', $route);
    }
  }

}
