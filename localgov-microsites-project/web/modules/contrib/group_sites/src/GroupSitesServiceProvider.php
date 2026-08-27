<?php

namespace Drupal\group_sites;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Alters existing services for the Group Sites module.
 */
class GroupSitesServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $services = [];

    foreach (array_keys($container->findTaggedServiceIds('group_sites_site_access_policy')) as $service_name) {
      $services[$service_name] = new Reference($service_name);
    }
    foreach (array_keys($container->findTaggedServiceIds('group_sites_no_site_access_policy')) as $service_name) {
      $services[$service_name] = new Reference($service_name);
    }

    $manager = $container->getDefinition('group_sites.access_policy');
    $manager->addArgument(ServiceLocatorTagPass::register($container, $services));
  }

}
