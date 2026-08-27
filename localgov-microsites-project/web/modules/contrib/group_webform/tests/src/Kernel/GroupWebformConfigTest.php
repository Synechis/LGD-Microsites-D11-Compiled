<?php

namespace Drupal\Tests\group_webform\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests that all config provided by this module passes validation.
 *
 * @group group_webform
 */
class GroupWebformConfigTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'entity',
    'flexible_permissions',
    'group_webform',
    'group',
    'webform',
    'options',
    'variationcache',
    'views',
  ];

  /**
   * Tests that the module's config installs properly.
   */
  public function testConfig(): void {
    $this->installConfig(['group_webform']);
  }

}
