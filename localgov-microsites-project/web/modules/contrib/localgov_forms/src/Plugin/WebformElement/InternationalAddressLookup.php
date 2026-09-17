<?php

declare(strict_types=1);

namespace Drupal\localgov_forms\Plugin\WebformElement;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'localgov_forms_international_address_lookup' webform element.
 *
 * @WebformElement(
 *   id = "localgov_forms_international_address_lookup",
 *   label = @Translation("International address lookup"),
 *   description = @Translation("UK geocode address lookup with a country field for international delivery addresses."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class InternationalAddressLookup extends UKAddressLookup {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties(): array {
    return parent::defineDefaultProperties() + ['country' => ''];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(array &$element, WebformSubmissionInterface $webform_submission): void {
    $submission_data = $webform_submission->getData();
    $webform = $webform_submission->getWebform();
    foreach ($submission_data as $key => $value) {
      $webform_element = $webform->getElement($key);
      if (($webform_element['#type'] ?? '') === 'localgov_forms_international_address_lookup') {
        $this->stripTransientFields($key, $submission_data);
      }
    }
    $webform_submission->setData($submission_data);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []): array {
    $value = $this->getValue($element, $webform_submission, $options);
    $line = implode(' ', array_filter([
      $value['address_1'] ?? '',
      $value['address_2'] ?? '',
      $value['town_city'] ?? '',
      $value['postcode'] ?? '',
      $value['country'] ?? '',
    ]));
    return $line ? [$line] : [];
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []): array {
    return $this->formatTextItemValue($element, $webform_submission, $options);
  }

}
