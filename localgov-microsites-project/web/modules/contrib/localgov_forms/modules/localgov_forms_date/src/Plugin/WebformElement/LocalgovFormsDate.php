<?php

namespace Drupal\localgov_forms_date\Plugin\WebformElement;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\Element\Datelist as CoreDatelist;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\DateList;

/**
 * Provides a 'localgov_forms_date' element.
 *
 * @WebformElement(
 *   id = "localgov_forms_date",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Datetime!Element!Datelist.php/class/Datelist",
 *   label = @Translation("LocalGov Forms Date"),
 *   description = @Translation("Provides a form element for date selection text fields."),
 *   category = @Translation("LocalGov Forms"),
 * )
 */
class LocalgovFormsDate extends DateList {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'date_min' => '',
      'date_max' => '',
      // Custom wording for the "not a real date" validation message.
      'date_invalid_error' => '',
      // Date settings.
      'date_part_order' => ['day', 'month', 'year'],
      'date_text_parts' => ['day', 'month', 'year'],
      'date_year_range' => '1900:2050',
      'date_increment' => 1,
      'date_abbreviate' => TRUE,
      '#options_display' => 'side_by_side',
    ] + parent::defineDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['date']['#title'] = $this->t('Date list settings');
    $form['date']['date_part_order'] = [
      '#type' => 'webform_tableselect_sort',
      '#header' => ['part' => 'Date part'],
      '#options' => [],
    ];
    $form['date']['date_text_parts'] = [
      '#type' => 'checkboxes',
      '#options_display' => 'side_by_side',
      '#title' => $this->t('Date text parts'),
      '#description' => $this->t("Select date parts that should be presented as text fields instead of drop-down selectors."),
      '#options' => [],
    ];
    $form['date']['date_year_range'] = [];
    $form['date']['date_year_range_reverse'] = [];
    $form['date']['date_increment'] = [];
    $form['date']['date_abbreviate'] = [];

    // Sits alongside webform's own "Required message", which it nests inside
    // $form['validation']['required_container'].
    $form['validation']['date_invalid_error'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Invalid date message'),
      '#description' => $this->t('Message shown when the date entered is not a real date, or is missing the day, month or year. Leave blank to use the default message.'),
      '#maxlength' => 255,
      // After the required message.
      '#weight' => 10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Without this the new message is not offered for translation, unlike
   * webform's own required_error.
   */
  protected function defineTranslatableProperties() {
    return array_merge(parent::defineTranslatableProperties(), ['date_invalid_error']);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::validateConfigurationForm($form, $form_state);
    $values = $form_state->getValues();
    $values['date_part_order'] = ['day', 'month', 'year'];
    $values['date_text_parts'] = ['day', 'month', 'year'];
    $form_state->setValues($values);

  }

  /**
   * After build handler for Datelist element.
   */
  public static function afterBuild(array $element, FormStateInterface $form_state): array {
    $element = parent::afterBuild($element, $form_state);

    // Set the property of the date of birth elements.
    $element['day']['#title'] = t('Day');
    $element['day']['#attributes']['placeholder'] = t('DD');
    $element['day']['#maxlength'] = 2;
    $element['day']['#attributes']['inputmode'] = 'numeric';
    $element['day']['#attributes']['pattern'] = '[0-9]*';
    $element['day']['#attributes']['class'][] = 'localgov_forms_date__day';

    $element['month']['#title'] = t('Month');
    $element['month']['#attributes']['placeholder'] = t('MM');
    $element['month']['#maxlength'] = 2;
    $element['month']['#attributes']['inputmode'] = 'numeric';
    $element['month']['#attributes']['pattern'] = '[0-9]*';
    $element['month']['#attributes']['class'][] = 'localgov_forms_date__month';

    $element['year']['#title'] = t('Year');
    $element['year']['#attributes']['placeholder'] = t('YYYY');
    $element['year']['#maxlength'] = 4;
    $element['year']['#attributes']['inputmode'] = 'numeric';
    $element['year']['#attributes']['pattern'] = '[0-9]*';
    $element['year']['#attributes']['class'][] = 'localgov_forms_date__year';

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * DateBase::setDefaultValue() can only process datelist and datetime
   * elements.  So here we pretend that we are a datelist element.  This is
   * particularly important when this element is used as part of a composite
   * element such as a multipage form.
   *
   * @see Drupal\Webform\Plugin\WebformElement\DateBase::setDefaultValue()
   */
  public function setDefaultValue(array &$element): void {

    $orig_type = $element['#type'];
    $element['#type'] = 'datelist';
    parent::setDefaultValue($element);
    $element['#type'] = $orig_type;
  }

  /**
   * {@inheritDoc}
   *
   * Overrides the Datebase class so that
   * date validation error messages display in
   * a UK Style format e.g dd-mm-yyyy.
   */
  public static function validateDate(&$element, FormStateInterface $form_state, &$complete_form): void {

    // Adds a  short date and short date time format.
    $localgov_forms_short_date_format = DateFormat::load('localgov_forms_date_short_date')->getPattern();
    $localgov_forms_datetime_format = DateFormat::load('localgov_forms_date_datetime')->getPattern();

    $element['#date_date_format'] = $localgov_forms_short_date_format;
    $element['#date_time_format'] = $localgov_forms_datetime_format;

    parent::validateDate($element, $form_state, $complete_form);
  }

  /**
   * {@inheritdoc}
   *
   * Ensure the datetime object gets added to the user input.
   * https://github.com/localgovdrupal/localgov_forms/issues/124
   */
  public static function preValidateDate(&$element, FormStateInterface $form_state, &$complete_form) {
    parent::preValidateDate($element, $form_state, $complete_form);

    // Repeating parent workaround to place datetime object on form_state
    // input.
    $input_exists = FALSE;
    $input = NestedArray::getValue($form_state->getValues(), $element['#parents'], $input_exists);
    if (!isset($input['object'])) {
      if (isset($element['#date_time_element']) && $element['#date_time_element'] === 'timepicker') {
        $element['#date_time_format'] = 'H:i:s';
      }
      $input = CoreDatelist::valueCallback($element, $input, $form_state);
      $form_state->setValueForElement($element, $input);
      $element['#value'] = $input;
    }
  }

}
