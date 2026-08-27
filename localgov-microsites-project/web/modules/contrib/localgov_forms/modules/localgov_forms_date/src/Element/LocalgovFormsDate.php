<?php

namespace Drupal\localgov_forms_date\Element;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Element\Datelist;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a datelist element.
 *
 * @FormElement("localgov_forms_date")
 */
class LocalgovFormsDate extends Datelist {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {

    return [
      '#input' => TRUE,
      '#element_validate' => [
        // Order matters.  Only the first error recorded against an element is
        // kept, so the most specific check runs first: a non-numeric date part
        // reports "must be a number" rather than the generic invalid message.
        [static::class, 'areDatePartsNumeric'],
        [static::class, 'validateDatelist'],
      ],
      '#process' => [
        [static::class, 'processDatelist'],
      ],
      '#theme' => 'datetime_form',
      '#theme_wrappers' => ['datetime_wrapper'],
      '#date_part_order' => ['day', 'month', 'year'],
      '#date_text_parts' => ['day', 'month', 'year'],
      '#date_year_range' => '1900:2050',
      '#date_increment' => 1,
      '#date_date_callbacks' => [],
      '#date_timezone' => date_default_timezone_get(),
    ];
  }

  /**
   * Wrapper over Datelist::valueCallback().
   *
   * Two jobs beyond core's:
   *
   * 1. Report the configured wording for complete input.  Core's
   *    Datelist::valueCallback() catches the exception from
   *    DrupalDateTime::createFromArray() and sets "Selected combination of day
   *    and month is not valid." during form build, earlier than any
   *    #element_validate callback.  Since FormState keeps only the first error
   *    per element, a message set during validation would be discarded, so it
   *    has to be set here.
   * 2. Absorb the \TypeError that Drupal 10 throws for a non-numeric date
   *    part.
   *
   * The two supported core majors fail differently:
   *
   * - Drupal 11: DateTimePlus::checkArray() guards with filter_var(), so bad
   *   input raises \InvalidArgumentException, which core's own catch block
   *   turns into its message.
   * - Drupal 10: checkArray() passes raw strings to checkdate(), so a
   *   non-numeric part raises \TypeError.  That extends \Error rather than
   *   \Exception, so core's catch does not see it and it escapes
   *   parent::valueCallback().  Hence the catch below - removing it fatals on
   *   Drupal 10.
   *
   * Claiming the error slot before delegating gives identical wording on both.
   *
   * @see https://www.drupal.org/project/drupal/issues/2818437
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {

    if (is_array($input) && !$form_state->isValidationComplete()) {
      $build_time_err_msg = static::findCompleteInputError($element, $input);

      if ($build_time_err_msg) {
        $form_state->setError($element, $build_time_err_msg);
      }
    }

    try {
      return parent::valueCallback($element, $input, $form_state);
    }
    catch (\TypeError $e) {
      // Drupal 10 only.  The message has usually been recorded above already,
      // making this a no-op; it remains the fallback for an element whose date
      // parts are not day, month and year.
      $form_state->setError($element, static::getInvalidErrorMessage($element));

      // Suppress PHP warning in Datelist::validateDatelist().
      $placeholder_date = DateTimePlus::createFromArray([], $element['#date_timezone']);
      $return = $input;
      $return['object'] = $placeholder_date;
      return $return;
    }
  }

  /**
   * Message for input core is about to reject, or NULL if it will not.
   *
   * Only complete input reaches DrupalDateTime::createFromArray(), so only
   * complete input can trigger core's build time message.  Staying silent for
   * partial input is deliberate rather than an optimisation: errors recorded
   * during form build survive #limit_validation_errors, so raising one where
   * core would not changes the behaviour of the "Save draft" and wizard
   * "Previous" buttons.
   */
  protected static function findCompleteInputError(array $element, array $input) {

    $parts = $element['#date_part_order'] ?? [];

    // Not a plain day, month and year element.  Leave it to core.
    if (array_diff(['day', 'month', 'year'], $parts)) {
      return NULL;
    }

    if (!empty(static::checkEmptyInputs($input, $parts))) {
      return NULL;
    }

    $non_numeric_err_msg = static::getNonNumericPartsMessage($element, $input);
    if ($non_numeric_err_msg) {
      return $non_numeric_err_msg;
    }

    // Mirrors DateTimePlus::checkArray(), so this fires exactly when core's
    // catch block would.
    if (!checkdate((int) $input['month'], (int) $input['day'], (int) $input['year'])) {
      return static::getInvalidErrorMessage($element);
    }

    return NULL;
  }

  /**
   * Validation callback.
   *
   * Are all the parts of a date numeric?  There are three parts we are
   * concerned about here: day, month, and year.  If any of these are not
   * numeric, validation fails.  When this happens, we restore the date parts
   * to what was originally submitted.  Note that the date parts go through an
   * integer conversion before they reach validation.  The purpose of the
   * restoration is to bring back what was originally submitted.
   *
   * Example: "1A" is submitted as the "day" value.  This turns into *1* as part
   * of form processing.  "1A" fails validation, so we restore the day value to
   * "1A".  If we don't do this, the day value will render as "1" instead of
   * "1A" along with validation errors.
   */
  public static function areDatePartsNumeric(&$element, FormStateInterface $form_state, &$complete_form) :void {

    $values = is_array($element['#value'] ?? NULL) ? $element['#value'] : [];
    $err_msg = static::getNonNumericPartsMessage($element, $values, $complete_form);

    if ($err_msg) {
      // For complete input ::valueCallback() has recorded the same message
      // already, making this a no-op, but the raw input still needs restoring.
      $form_state->setError($element, $err_msg);
      static::restoreUnprocessedDate($element);
    }
  }

  /**
   * Date input restoration.
   *
   * Returns date part values to the raw input.  This raw input has gone through
   * an integer conversion as part of form processing.  Here we restore the
   * original raw string values.
   */
  private static function restoreUnprocessedDate(array &$element) :void {

    if (isset($element['#value']['year'])) {
      $element['year']['#value'] = $element['#value']['year'];
    }

    if (isset($element['#value']['month'])) {
      $element['month']['#value'] = $element['#value']['month'];
    }

    if (isset($element['#value']['day'])) {
      $element['day']['#value'] = $element['#value']['day'];
    }
  }

  /**
   * Validation callback.
   *
   * Reports the wording configured on the element instead of core's, which
   * cannot be altered from the webform UI and reads badly: "The Date of birth
   * date is required." duplicates the word "date" for any element whose title
   * mentions one.
   *
   * Core's branch decisions are mirrored here, but only the two branches with
   * value side effects are delegated to core.  The other two set errors and
   * nothing else, so handling them here costs nothing - and it keeps core from
   * recording an error against each missing date part on top of the element's
   * own message.
   */
  public static function validateDatelist(&$element, FormStateInterface $form_state, &$complete_form): void {

    $input_exists = FALSE;
    $input = NestedArray::getValue($form_state->getValues(), $element['#parents'], $input_exists);

    if (!$input_exists) {
      return;
    }

    // The same distinction core makes: nothing entered at all, versus some
    // parts entered.  checkEmptyInputs() is core's own helper, so "0" counts
    // as entered here exactly as it does upstream.
    $empty_parts = static::checkEmptyInputs($input, $element['#date_part_order']);
    $all_empty = empty($input['day']) && empty($input['month']) && empty($input['year']);

    if ($all_empty && !empty($element['#required'])) {
      $form_state->setError($element, static::getRequiredErrorMessage($element, $complete_form));
      return;
    }

    if (!$all_empty && $empty_parts) {
      $form_state->setError($element, static::getIncompleteErrorMessage($element, $empty_parts, $complete_form));
      return;
    }

    // Remaining branches touch values, so core runs them: everything empty and
    // not required, where core sets the value to NULL, or every part filled,
    // where core sets the date object.  Claim the error slot first so the
    // invalid wording is ours.  For complete input ::valueCallback() has
    // usually done so already, and core's own invalid message is guarded by a
    // getError() check in any case.
    $date = $input['object'] ?? NULL;
    if (!$all_empty && (!$date instanceof DrupalDateTime || $date->hasErrors())) {
      $form_state->setError($element, static::getInvalidErrorMessage($element, $complete_form));
    }

    parent::validateDatelist($element, $form_state, $complete_form);
  }

  /**
   * Message for an empty, required date.
   *
   * Returns the element's "Required message" setting verbatim: no placeholder,
   * so no <em class="placeholder"> around the content designer's text.
   */
  protected static function getRequiredErrorMessage(array $element, ?array $complete_form = NULL) {

    if (!empty($element['#required_error'])) {
      return $element['#required_error'];
    }

    return t('@title is required.', [
      '@title' => static::getErrorTitle($element, $complete_form),
    ]);
  }

  /**
   * Message for a date that is missing one or two of its parts.
   */
  protected static function getIncompleteErrorMessage(array $element, array $empty_parts, ?array $complete_form = NULL) {

    if (!empty($element['#date_invalid_error'])) {
      return $element['#date_invalid_error'];
    }

    $part_labels = array_map([static::class, 'getDatePartLabel'], $empty_parts);

    return t('@title must include a @parts.', [
      '@title' => static::getErrorTitle($element, $complete_form),
      '@parts' => implode(' and a ', $part_labels),
    ]);
  }

  /**
   * Message for a date that is not a real date.
   *
   * Avoids core's "The %field date is invalid", which duplicates the word
   * "date" for any element titled "Date", "Date of birth" and so on, and
   * core's build time "Selected combination of day and month is not valid.",
   * which blames the day and month even when the year is at fault.
   */
  protected static function getInvalidErrorMessage(array $element, ?array $complete_form = NULL) {

    if (!empty($element['#date_invalid_error'])) {
      return $element['#date_invalid_error'];
    }

    return t('@title must be a real date.', [
      '@title' => static::getErrorTitle($element, $complete_form),
    ]);
  }

  /**
   * Message naming any date parts that are not numeric.
   *
   * Shared by ::valueCallback() and ::areDatePartsNumeric() so the wording is
   * the same whichever of them reports first.  Empty parts are not reported
   * here; they are the incomplete case.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string|null
   *   The message, or NULL when every date part is numeric or empty.
   */
  protected static function getNonNumericPartsMessage(array $element, array $values, ?array $complete_form = NULL) {

    $title = static::getErrorTitle($element, $complete_form);
    $err_msg = [];

    if (!static::isNumericDatePart($values['day'] ?? '')) {
      $err_msg[] = t('The day in @title must be a number.', ['@title' => $title]);
    }
    if (!static::isNumericDatePart($values['month'] ?? '')) {
      $err_msg[] = t('The month in @title must be a number.', ['@title' => $title]);
    }
    if (!static::isNumericDatePart($values['year'] ?? '')) {
      $err_msg[] = t('The year in @title must be a number.', ['@title' => $title]);
    }

    if (!$err_msg) {
      return NULL;
    }

    // Configured wording replaces the per-part detail rather than being
    // appended to it.
    if (!empty($element['#date_invalid_error'])) {
      return $element['#date_invalid_error'];
    }

    return implode(' ', array_map('strval', $err_msg));
  }

  /**
   * Is a single date part usable as a number?  Empty counts as usable.
   */
  protected static function isNumericDatePart($value) :bool {

    return $value === NULL || $value === '' || ctype_digit((string) $value);
  }

  /**
   * Element title for use in a default message.
   *
   * Prefers core's helper, which finds the title on a parent container when
   * the element is part of a composite.  That helper returns an empty string
   * when neither the element nor its parent has a title, which would render as
   * " is required.", hence the fallback.  #title alone is used where no
   * complete form is available, that is inside ::valueCallback().
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The title to name the element by.
   */
  protected static function getErrorTitle(array $element, ?array $complete_form = NULL) {

    $title = $complete_form === NULL
      ? ($element['#title'] ?? '')
      : static::getElementTitle($element, $complete_form);

    return (string) $title === '' ? t('Date') : $title;
  }

  /**
   * Lower case, translatable name of a date part.
   */
  protected static function getDatePartLabel(string $part) :string {

    return (string) match ($part) {
      'day' => t('day'),
      'month' => t('month'),
      'year' => t('year'),
      default => $part,
    };
  }

}
