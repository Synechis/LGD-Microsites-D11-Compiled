<?php

declare(strict_types=1);

namespace Drupal\Tests\localgov_forms_date\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionForm;

/**
 * Tests the validation messages of the LocalGov Forms date elements.
 *
 * Each case asserts the *complete* error set for a submission rather than a
 * single element's message.  Errors are keyed by element path, so an unwanted
 * message against one of the day, month or year sub-elements shows up as an
 * extra key.
 */
class DateValidationMessageTest extends KernelTestBase {

  /**
   * A valid date, used to fill the elements a case is not exercising.
   */
  const VALID_DATE = ['day' => '1', 'month' => '2', 'year' => '2003'];

  /**
   * An empty date.
   */
  const EMPTY_DATE = ['day' => '', 'month' => '', 'year' => ''];

  /**
   * Tests the message for an empty, required date.
   */
  public function testDefaultRequiredMessage(): void {

    $this->assertMessages(
      ['plain_required' => self::EMPTY_DATE],
      ['plain_required' => 'Date of birth is required.']
    );
  }

  /**
   * Tests that the configured required message is used verbatim.
   */
  public function testCustomRequiredMessage(): void {

    $this->assertMessages(
      ['custom_required' => self::EMPTY_DATE],
      ['custom_required' => 'Enter your date of birth']
    );
  }

  /**
   * Tests the configured required message on the date of birth element.
   */
  public function testCustomRequiredMessageOnDateOfBirthElement(): void {

    $this->assertMessages(
      ['dob_custom' => self::EMPTY_DATE],
      ['dob_custom' => 'Enter your date of birth']
    );
  }

  /**
   * Tests the fallback title where the element has none.
   *
   * Core's DateElementBase::getElementTitle() returns an empty string in this
   * situation, which would render as " is required.".
   */
  public function testUntitledElementMessage(): void {

    $this->assertMessages(
      ['untitled_required' => self::EMPTY_DATE],
      ['untitled_required' => 'Date is required.']
    );
  }

  /**
   * Tests that a partly filled date names the missing parts.
   */
  public function testIncompleteMessages(): void {

    $this->assertMessages(
      ['optional_date' => ['day' => '1', 'month' => '', 'year' => '']],
      ['optional_date' => 'Date of birth must include a month and a year.']
    );

    $this->assertMessages(
      ['optional_date' => ['day' => '1', 'month' => '2', 'year' => '']],
      ['optional_date' => 'Date of birth must include a year.']
    );

    // A required element reports the missing parts too, not "is required".
    $this->assertMessages(
      ['plain_required' => ['day' => '1', 'month' => '', 'year' => '']],
      ['plain_required' => 'Date of birth must include a month and a year.']
    );
  }

  /**
   * Tests the configured message for a partly filled date.
   */
  public function testIncompleteMessageWithCustomWording(): void {

    $this->assertMessages(
      ['custom_invalid' => ['day' => '1', 'month' => '', 'year' => '']],
      ['custom_invalid' => 'Date of birth must be a real date']
    );
  }

  /**
   * Tests the message for a date that does not exist.
   *
   * Core reports these from Datelist::valueCallback() during form build, before
   * any #element_validate callback runs, so this is the case a fix confined to
   * ::validateDatelist() would miss.
   */
  public function testUnrealDateMessages(): void {

    $this->assertMessages(
      ['plain_required' => ['day' => '31', 'month' => '2', 'year' => '2024']],
      ['plain_required' => 'Date of birth must be a real date.']
    );

    $this->assertMessages(
      ['custom_invalid' => ['day' => '31', 'month' => '2', 'year' => '2024']],
      ['custom_invalid' => 'Date of birth must be a real date']
    );
  }

  /**
   * Tests that zeroes are reported as an unreal date.
   *
   * PHP's empty() treats "0" as empty while core's checkEmptyInputs() does not,
   * so this pins down which of the two paths a zero takes.
   */
  public function testZeroDateMessage(): void {

    $this->assertMessages(
      ['plain_required' => ['day' => '0', 'month' => '0', 'year' => '0']],
      ['plain_required' => 'Date of birth must be a real date.']
    );
  }

  /**
   * Tests the message for non-numeric input in a complete date.
   */
  public function testCompleteNonNumericMessages(): void {

    $this->assertMessages(
      ['plain_required' => ['day' => '1A', 'month' => '2', 'year' => '2003']],
      ['plain_required' => 'The day in Date of birth must be a number.']
    );

    $this->assertMessages(
      ['plain_required' => ['day' => '1', 'month' => '2', 'year' => 'Last year']],
      ['plain_required' => 'The year in Date of birth must be a number.']
    );

    // The configured wording replaces the per-part detail.
    $this->assertMessages(
      ['custom_invalid' => ['day' => '1A', 'month' => '2', 'year' => '2003']],
      ['custom_invalid' => 'Date of birth must be a real date']
    );
  }

  /**
   * Tests the message for non-numeric input in a partly filled date.
   */
  public function testPartialNonNumericMessage(): void {

    $this->assertMessages(
      ['plain_required' => ['day' => '1A', 'month' => '', 'year' => '']],
      ['plain_required' => 'The day in Date of birth must be a number.']
    );
  }

  /**
   * Tests that an empty, optional date raises nothing.
   */
  public function testEmptyOptionalDate(): void {

    $this->assertMessages(['optional_date' => self::EMPTY_DATE], []);
  }

  /**
   * Tests that a valid submission raises nothing.
   */
  public function testValidSubmission(): void {

    $this->assertMessages([], []);
  }

  /**
   * Tests that no message italicises the field name.
   *
   * Casting the message to a string expands any placeholder, so a "%" style
   * placeholder shows up here as <em class="placeholder">.
   */
  public function testMessagesAreNotItalicised(): void {

    $cases = [
      ['plain_required' => self::EMPTY_DATE],
      ['custom_required' => self::EMPTY_DATE],
      ['optional_date' => ['day' => '1', 'month' => '', 'year' => '']],
      ['plain_required' => ['day' => '31', 'month' => '2', 'year' => '2024']],
      ['plain_required' => ['day' => '1A', 'month' => '2', 'year' => '2003']],
    ];

    foreach ($cases as $case) {
      $errors = $this->submitDates($case);
      $this->assertNotEmpty($errors);

      foreach ($errors as $error) {
        $this->assertStringNotContainsString('<em', $error);
      }
    }
  }

  /**
   * Submits the test form and asserts the resulting messages.
   *
   * @param array $dates
   *   Date values keyed by element name.  Elements left out are filled with a
   *   valid date where they are required, and omitted otherwise.
   * @param array $expected
   *   The complete set of expected error messages, keyed by element name.
   */
  protected function assertMessages(array $dates, array $expected): void {

    $this->assertSame($expected, $this->submitDates($dates));
  }

  /**
   * Submits the test form and returns its error messages as strings.
   *
   * @param array $dates
   *   Date values keyed by element name.
   *
   * @return array
   *   Error messages keyed by element path.
   */
  protected function submitDates(array $dates): array {

    $values = $dates + [
      'plain_required' => self::VALID_DATE,
      'custom_required' => self::VALID_DATE,
      'untitled_required' => self::VALID_DATE,
      'dob_custom' => self::VALID_DATE,
    ];

    $form_state = new FormState();
    foreach ($values as $element_name => $date) {
      $form_state->setValue($element_name, $date);
    }
    $form_state->setValue('op', 'Submission');

    $test_form = clone($this->testForm);
    $this->container->get('form_builder')->submitForm($test_form, $form_state);

    return array_map('strval', $form_state->getErrors());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('path_alias');
    $this->installSchema('webform', ['webform']);
    $this->installConfig('system');
    $this->installConfig('webform');
    $this->installConfig('localgov_forms_date_test');
    $this->installConfig('localgov_forms_date');

    $empty_submission = WebformSubmission::create(['webform_id' => 'date_message_test']);
    $this->testForm = WebformSubmissionForm::create($this->container);
    $this->testForm->setEntityTypeManager($this->container->get('entity_type.manager'));
    $this->testForm->setModuleHandler($this->container->get('module_handler'));
    $this->testForm->setEntity($empty_submission);
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'path',
    'path_alias',
    'webform',
    'localgov_forms_date',
    'localgov_forms_date_test',
  ];

  /**
   * Webform submission form.
   *
   * @var \Drupal\webform\WebformSubmissionForm
   */
  protected $testForm;

}
