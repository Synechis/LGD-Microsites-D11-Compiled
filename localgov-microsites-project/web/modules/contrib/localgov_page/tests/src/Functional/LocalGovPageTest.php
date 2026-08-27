<?php

namespace Drupal\Tests\localgov_page\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\node\NodeInterface;

/**
 * Tests localgov page pages.
 *
 * @group localgov_page
 */
class LocalGovPageTest extends BrowserTestBase {

  use NodeCreationTrait;

  /**
   * Use testing profile.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * Use stark theme.
   *
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field_ui',
    'options',
    'node',
    'localgov_core',
    'localgov_media',
    'localgov_media_test_config',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->get('module_installer')->install(['localgov_paragraphs']);
    $this->container->get('module_installer')->install(['localgov_paragraphs_layout']);
    $this->container->get('module_installer')->install(['localgov_page']);
    $this->rebuildContainer();

    $admin_user = $this->drupalCreateUser([
      'administer node fields',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Test fields on page.
   */
  public function testPageFields(): void {

    // Check all fields exist.
    $this->drupalGet('/admin/structure/types/manage/localgov_page/fields');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('localgov_page_banner');
    $this->assertSession()->pageTextContains('localgov_paragraph_content');
    $this->assertSession()->pageTextContains('localgov_page_summary');

    // Test fields display.
    $title = $this->randomMachineName(8);
    $summary = $this->randomMachineName(16);
    $page = $this->createNode([
      'type' => 'localgov_page',
      'title' => $title,
      'localgov_page_summary' => ['value' => $summary],
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->drupalGet('/node/' . $page->id());
    $this->assertSession()->pageTextContains($title);
    $this->assertSession()->pageTextContains($summary);
  }

}
