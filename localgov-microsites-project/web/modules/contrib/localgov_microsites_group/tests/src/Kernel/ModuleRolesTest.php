<?php

namespace Drupal\Tests\localgov_microsites_group\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\group\Entity\GroupRole;
use Drupal\localgov_microsites_group\RolesHelper;
use Drupal\user\Entity\Role;

/**
 * Tests ModuleHandler functionality.
 *
 * @group localgov_roles
 */
class ModuleRolesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'domain',
    'system',
    'user',
    'node',
    'localgov_core',
    'localgov_media',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    $this->installConfig([
      'system',
      'user',
      'node',
      'localgov_core',
      'localgov_media',
    ]);
  }

  /**
   * Test permissions are assigned as expected.
   */
  public function testPermissionsAssignedWhenEnablingModules() {

    // Syncing mode skips config dependency checks, avoiding failures caused
    // by unmet optional config (e.g. filter formats, media types) in the
    // localgov_paragraphs dependency chain.
    $this->container->get('config.installer')->setSyncing(TRUE);
    $this->container->get('module_installer')->install([
      'localgov_microsites_roles_test',
    ]);
    $this->container->get('config.installer')->setSyncing(FALSE);

    // Test global permissions.
    $microsites_controller = Role::load(RolesHelper::MICROSITES_CONTROLLER_ROLE);
    $this->assertFalse($microsites_controller->hasPermission('administer microsites roles test'));
    $this->assertTrue($microsites_controller->hasPermission('create microsites roles test'));
    $this->assertTrue($microsites_controller->hasPermission('delete microsites roles test'));
    $this->assertTrue($microsites_controller->hasPermission('edit microsites roles test'));
    $this->assertTrue($microsites_controller->hasPermission('view microsites roles test'));

    $microsites_trusted_editor = Role::load(RolesHelper::MICROSITES_EDITOR_ROLE);
    $this->assertFalse($microsites_trusted_editor->hasPermission('administer microsites roles test'));
    $this->assertTrue($microsites_trusted_editor->hasPermission('create microsites roles test'));
    $this->assertFalse($microsites_trusted_editor->hasPermission('delete microsites roles test'));
    $this->assertTrue($microsites_trusted_editor->hasPermission('edit microsites roles test'));
    $this->assertTrue($microsites_trusted_editor->hasPermission('view microsites roles test'));

    // Test microsite group permissions.
    $microsite_admin = GroupRole::load('microsite-' . RolesHelper::GROUP_ADMIN_ROLE);
    $this->assertTrue($microsite_admin->hasPermission('administer microsite roles test'));
    $this->assertTrue($microsite_admin->hasPermission('create microsite roles test'));
    $this->assertTrue($microsite_admin->hasPermission('delete microsite roles test'));
    $this->assertTrue($microsite_admin->hasPermission('edit microsite roles test'));
    $this->assertTrue($microsite_admin->hasPermission('view microsite roles test'));

    $microsite_anon = GroupRole::load('microsite-' . RolesHelper::GROUP_ANONYMOUS_ROLE);
    $this->assertFalse($microsite_anon->hasPermission('administer microsite roles test'));
    $this->assertFalse($microsite_anon->hasPermission('create microsite roles test'));
    $this->assertFalse($microsite_anon->hasPermission('delete microsite roles test'));
    $this->assertFalse($microsite_anon->hasPermission('edit microsite roles test'));
    $this->assertTrue($microsite_anon->hasPermission('view microsite roles test'));

    $microsite_member = GroupRole::load('microsite-' . RolesHelper::GROUP_MEMBER_ROLE);
    $this->assertFalse($microsite_member->hasPermission('administer microsite roles test'));
    $this->assertTrue($microsite_member->hasPermission('create microsite roles test'));
    $this->assertTrue($microsite_member->hasPermission('delete microsite roles test'));
    $this->assertTrue($microsite_member->hasPermission('edit microsite roles test'));
    $this->assertTrue($microsite_member->hasPermission('view microsite roles test'));

    $microsite_outsider = GroupRole::load('microsite-' . RolesHelper::GROUP_OUTSIDER_ROLE);
    $this->assertFalse($microsite_outsider->hasPermission('administer microsite roles test'));
    $this->assertFalse($microsite_outsider->hasPermission('create microsite roles test'));
    $this->assertFalse($microsite_outsider->hasPermission('delete microsite roles test'));
    $this->assertFalse($microsite_outsider->hasPermission('edit microsite roles test'));
    $this->assertTrue($microsite_outsider->hasPermission('view microsite roles test'));
  }

}
