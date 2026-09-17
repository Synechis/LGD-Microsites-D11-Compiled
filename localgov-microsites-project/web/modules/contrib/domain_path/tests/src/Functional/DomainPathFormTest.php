<?php

namespace Drupal\Tests\domain_path\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\language\Traits\LanguageTestTrait;

/**
 * Tests the Domain Path form.
 *
 * @group domain_path
 */
class DomainPathFormTest extends DomainPathTestBase {

  use LanguageTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->createLanguageFromLangcode('de');

    // Adding languages requires a container rebuild in the test-running
    // environment so that multilingual services are used.
    $this->rebuildContainer();
  }

  /**
   * Test the Domain Path form.
   */
  public function testDomainPathForm() {
    $nodeEN = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'field_domain_access' => 'example_com',
      'langcode' => 'en',
    ]);
    $nodeEN->save();

    $nodeDE = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'field_domain_access' => 'example_com',
      'langcode' => 'de',
    ]);
    $nodeDE->save();

    $this->drupalGet('/admin/config/domain_path');
    $this->clickLink('Add Domain path');
    $this->fillField('domain_id[0][target_id]', 'domain (example_com)');
    $this->fillField('source[0][value]', '/node/' . $nodeEN->id());
    $this->fillField('alias[0][value]', '/foo');
    $this->selectFieldOption('language', 'en');
    $this->pressButton('Save');

    $this->drupalGet('/admin/config/domain_path');
    $this->clickLink('Add Domain path');
    $this->fillField('domain_id[0][target_id]', 'domain (example_com)');
    $this->fillField('source[0][value]', '/node/' . $nodeDE->id());
    $this->fillField('alias[0][value]', '/foo');
    $this->selectFieldOption('language', 'de');
    $this->pressButton('Save');

    $this->drupalGet('/foo');
    $this->assertSession()->pageTextContains($nodeEN->label());

    $this->drupalGet('/de/foo');
    $this->assertSession()->pageTextContains($nodeDE->label());
  }

}
