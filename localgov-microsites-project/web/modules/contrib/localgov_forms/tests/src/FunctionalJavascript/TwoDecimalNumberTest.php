<?php

declare(strict_types=1);

namespace Drupal\Tests\localgov_forms\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests two-decimal number element JS behaviour.
 *
 * Covers attribute rendering, blur normalisation, submit normalisation
 * (single and multi-field), and that unflagged number elements are unaffected.
 */
class TwoDecimalNumberTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['localgov_forms', 'localgov_forms_test', 'webform'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Flagged fields have the expected HTML attributes.
   */
  public function testAttributesPresent(): void {
    $assert = $this->assertSession();

    $this->drupalGet('/webform/two_decimal_test');

    $assert->elementAttributeContains('css', '#edit-price', 'data-two-decimal', 'true');
    $assert->elementAttributeContains('css', '#edit-price', 'step', '0.01');
    $assert->elementAttributeContains('css', '#edit-price', 'inputmode', 'decimal');
  }

  /**
   * Unflagged number fields do not get two-decimal attributes.
   */
  public function testUnflaggedFieldUnaffected(): void {
    $assert = $this->assertSession();

    $this->drupalGet('/webform/two_decimal_test');

    $assert->elementAttributeNotExists('css', '#edit-quantity', 'data-two-decimal');
  }

  /**
   * Value is normalised to two decimal places on blur.
   */
  public function testBlurNormalises(): void {
    $this->drupalGet('/webform/two_decimal_test');

    $js = <<<JS
      (function () {
        var field = document.querySelector('#edit-price');
        field.value = '1';
        field.dispatchEvent(new Event('blur'));
        return field.value;
      })();
    JS;

    $value = $this->getSession()->evaluateScript($js);
    $this->assertEquals('1.00', $value);
  }

  /**
   * Value is normalised to two decimal places on form submit (single field).
   */
  public function testSubmitNormalisesSingleField(): void {
    $this->drupalGet('/webform/two_decimal_test');

    // Set value without triggering blur, then submit.
    $this->getSession()->evaluateScript("document.querySelector('#edit-price').value = '5';");
    $this->getSession()->getPage()->pressButton('Submit');
    $this->assertSession()->waitForElementVisible('css', '.webform-confirmation__message');

    $submissions = \Drupal::entityTypeManager()
      ->getStorage('webform_submission')
      ->loadByProperties(['webform_id' => 'two_decimal_test']);
    $submission = end($submissions);
    $this->assertEquals('5.00', $submission->getElementData('price'));
  }

  /**
   * All two-decimal fields on a form are normalised on submit.
   *
   * Catches the original bug where only the first flagged field was normalised
   * because the submit listener was gated by a per-form flag.
   */
  public function testSubmitNormalisesMultipleFields(): void {
    $this->drupalGet('/webform/two_decimal_test');

    $this->getSession()->evaluateScript("document.querySelector('#edit-price').value = '3';");
    $this->getSession()->evaluateScript("document.querySelector('#edit-price2').value = '7';");
    $this->getSession()->getPage()->pressButton('Submit');
    $this->assertSession()->waitForElementVisible('css', '.webform-confirmation__message');

    $submissions = \Drupal::entityTypeManager()
      ->getStorage('webform_submission')
      ->loadByProperties(['webform_id' => 'two_decimal_test']);
    $submission = end($submissions);
    $this->assertEquals('3.00', $submission->getElementData('price'));
    $this->assertEquals('7.00', $submission->getElementData('price2'));
  }

}
