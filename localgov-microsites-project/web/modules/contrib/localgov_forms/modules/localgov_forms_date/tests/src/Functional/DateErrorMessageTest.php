<?php

declare(strict_types=1);

namespace Drupal\Tests\localgov_forms_date\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests what a form filler reads when a LocalGov Forms date fails validation.
 *
 * The kernel tests pin down the messages themselves.  This covers the parts
 * only a real request shows: that core's own wording is gone, that nothing
 * italicises the field name, and that the raw input is still in the boxes.
 */
class DateErrorMessageTest extends BrowserTestBase {

  /**
   * A valid date, used to fill the elements a case is not exercising.
   */
  const VALID_DATE = ['day' => '1', 'month' => '2', 'year' => '2003'];

  /**
   * Tests the messages for empty, required dates.
   */
  public function testRequiredMessages(): void {

    $session_assert = $this->assertSession();

    $this->drupalGet('/webform/date_message_test');
    $this->submitForm([], 'Submit');

    $session_assert->pageTextContains('Date of birth is required.');
    $session_assert->pageTextContains('Enter your date of birth');

    // Core's per-part wording, which the element must never produce.
    $session_assert->pageTextNotContains('field is required');
    $session_assert->responseNotContains('<em class="placeholder">');
  }

  /**
   * Tests the message for a partly filled date.
   */
  public function testIncompleteMessage(): void {

    $session_assert = $this->assertSession();

    $this->drupalGet('/webform/date_message_test');
    $this->submitForm($this->formValues([
      'optional_date' => ['day' => '1', 'month' => '', 'year' => ''],
    ]), 'Submit');

    $session_assert->pageTextContains('Date of birth must include a month and a year.');

    // Core's per-part wording for this branch.
    $session_assert->pageTextNotContains('A value must be selected for');
    $session_assert->responseNotContains('<em class="placeholder">');
  }

  /**
   * Tests the message for a date that does not exist.
   */
  public function testUnrealDateMessage(): void {

    $session_assert = $this->assertSession();

    $this->drupalGet('/webform/date_message_test');
    $this->submitForm($this->formValues([
      'plain_required' => ['day' => '31', 'month' => '2', 'year' => '2024'],
    ]), 'Submit');

    $session_assert->pageTextContains('Date of birth must be a real date.');

    // Core's build time wording.
    $session_assert->pageTextNotContains('Selected combination of day and month');
    $session_assert->responseNotContains('<em class="placeholder">');
  }

  /**
   * Tests the message for non-numeric input, and that the input is kept.
   */
  public function testNonNumericMessage(): void {

    $session_assert = $this->assertSession();

    $this->drupalGet('/webform/date_message_test');
    $this->submitForm($this->formValues([
      'plain_required' => ['day' => '1A', 'month' => '2', 'year' => '2003'],
    ]), 'Submit');

    $session_assert->pageTextContains('The day in Date of birth must be a number.');
    $session_assert->pageTextNotContains('Selected combination of day and month');

    // The box keeps what was typed rather than an integer conversion of it.
    $session_assert->fieldValueEquals('plain_required[day]', '1A');

    // Same again where the rest of the date is empty, which is reported by
    // ::areDatePartsNumeric() rather than at build time.
    $this->drupalGet('/webform/date_message_test');
    $this->submitForm($this->formValues([
      'plain_required' => ['day' => '1A', 'month' => '', 'year' => ''],
    ]), 'Submit');

    $session_assert->pageTextContains('The day in Date of birth must be a number.');
    $session_assert->fieldValueEquals('plain_required[day]', '1A');
  }

  /**
   * Builds form values, filling every required element with a valid date.
   *
   * @param array $dates
   *   Date values keyed by element name.
   *
   * @return array
   *   Values keyed by form field name.
   */
  protected function formValues(array $dates): array {

    $dates += [
      'plain_required' => self::VALID_DATE,
      'custom_required' => self::VALID_DATE,
      'untitled_required' => self::VALID_DATE,
      'dob_custom' => self::VALID_DATE,
    ];

    $values = [];
    foreach ($dates as $element_name => $date) {
      foreach ($date as $part => $value) {
        $values["{$element_name}[{$part}]"] = $value;
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'localgov_forms_date',
    'localgov_forms_date_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
