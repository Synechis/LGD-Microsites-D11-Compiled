<?php

namespace Drupal\Tests\group_sites\Kernel;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Tests the Group Sites negotiator.
 *
 * @coversDefaultClass \Drupal\group_sites\GroupSitesNegotiator
 * @group group_sites
 */
class GroupSitesNegotiatorTest extends GroupSitesKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['group_sites_test'];

  /**
   * Tests that the right group is returned from the configured context.
   *
   * @covers ::getActiveGroup
   */
  public function testGetGroupFromContext() {
    $this->createGroupType(['id' => 'foo', 'creator_membership' => FALSE]);
    $group = $this->createGroup(['type' => 'foo']);

    $negotiator = $this->container->get('group_sites.negotiator');

    $this->assertEquals(NULL, $negotiator->getActiveGroup());
    $cacheable_metadata = new CacheableMetadata();
    $this->assertEquals(NULL, $negotiator->getActiveGroup($cacheable_metadata));
    $this->assertEqualsCanonicalizing([], $cacheable_metadata->getCacheContexts());
    $this->assertEqualsCanonicalizing(['config:group_sites.settings'], $cacheable_metadata->getCacheTags());

    $this->config('group_sites.settings')
      ->set('context_provider', '@group_sites_test.hardcoded_group_context:group')
      ->save();

    $this->assertEquals($group->id(), $negotiator->getActiveGroup()->id());
    $cacheable_metadata = new CacheableMetadata();
    $this->assertEquals($group->id(), $negotiator->getActiveGroup($cacheable_metadata)->id());
    $this->assertEqualsCanonicalizing([], $cacheable_metadata->getCacheContexts());
    $this->assertEqualsCanonicalizing(['group:1', 'config:group_sites.settings'], $cacheable_metadata->getCacheTags());
  }

}
