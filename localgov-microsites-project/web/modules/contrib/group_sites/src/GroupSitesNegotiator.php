<?php

namespace Drupal\group_sites;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Retrieves the active group from the configured context provider.
 *
 * @internal
 */
final class GroupSitesNegotiator {

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected ContextRepositoryInterface $contextRepository,
  ) {}

  /**
   * Gets the active group from the configured context provider.
   *
   * @param \Drupal\Core\Cache\CacheableMetadata|null $cacheable_metadata
   *   (optional) A cacheable metadata object to add to if you care about the
   *   cacheable metadata from the retrieval of the group.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The active group from the configured context provider.
   */
  public function getActiveGroup(?CacheableMetadata $cacheable_metadata = NULL): GroupInterface|null {
    $cacheable_metadata?->addCacheTags(['config:group_sites.settings']);

    $context_id = $this->configFactory->get('group_sites.settings')->get('context_provider');
    if ($context_id === NULL) {
      return NULL;
    }

    $contexts = $this->contextRepository->getRuntimeContexts([$context_id]);
    if ($context = reset($contexts) ?: NULL) {
      $cacheable_metadata?->addCacheableDependency($context);
    }

    if ($group = $context?->getContextValue()) {
      if (!$group instanceof GroupInterface) {
        throw new \InvalidArgumentException('Context value is not a Group entity.');
      }
      return $group;
    }

    return NULL;
  }

}
