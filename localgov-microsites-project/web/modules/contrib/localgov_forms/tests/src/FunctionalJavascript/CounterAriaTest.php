<?php

declare(strict_types=1);

namespace Drupal\Tests\localgov_forms\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests that webform counter elements are linked to their textareas via ARIA.
 *
 * Uses aria-describedby so the counter is supplementary information and does
 * not override the field's existing label association.
 */
class CounterAriaTest extends WebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['localgov_forms', 'localgov_forms_test', 'webform'];

  /**
   * As it says on the tin.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the counter element receives a non-empty id attribute.
   */
  public function testCounterReceivesId(): void {
    $this->drupalGet('/webform/counter_aria_test');
    $session = $this->assertSession();

    $session->waitForElementVisible('css', '.text-count-message');
    $counter_id = $this->getSession()->evaluateScript(
      "document.querySelector('.text-count-message')?.id"
    );

    $this->assertNotEmpty($counter_id, 'Counter element must have a non-empty id.');
  }

  /**
   * Tests that the textarea's aria-describedby references the counter id.
   */
  public function testTextareaAriaDescribedBy(): void {
    $this->drupalGet('/webform/counter_aria_test');
    $session = $this->assertSession();

    $session->waitForElementVisible('css', '.js-webform-counter');
    $counter_id = $this->getSession()->evaluateScript(
      "document.querySelector('.text-count-message')?.id"
    );
    $this->assertNotEmpty($counter_id);

    $aria = $this->getSession()->evaluateScript(
      "document.querySelector('.js-webform-counter')?.getAttribute('aria-describedby')"
    );

    $this->assertNotEmpty($aria, 'Counter textarea must have aria-describedby.');
    $this->assertStringContainsString($counter_id, $aria, 'aria-describedby must reference the counter id.');
  }

  /**
   * Tests that a plain textarea without a counter gets no aria-describedby.
   */
  public function testPlainTextareaHasNoAriaDescribedBy(): void {
    $this->drupalGet('/webform/counter_aria_test');
    $session = $this->assertSession();

    $session->waitForElementVisible('css', '#edit-plain-message');
    $aria = $this->getSession()->evaluateScript(
      "document.querySelector('#edit-plain-message')?.getAttribute('aria-describedby')"
    );

    $this->assertNull($aria, 'Plain textarea must not receive aria-describedby from this behaviour.');
  }

}
