<?php

declare(strict_types=1);

namespace Drupal\localgov_forms\Element;

/**
 * Provides a 'localgov_forms_international_address_lookup' form element.
 *
 * Extends the LocalGov UK address lookup composite with a country select field
 * for international delivery addresses. The UK geocode lookup is retained so
 * users can search for a UK address and then override the country if needed.
 *
 * The country select is populated from the geocoded address by
 * js/localgov_forms_international_address_lookup.js. Webform's country_names
 * options are keyed by the translated country name, so on a non-English site
 * the geocoder's English country name may not match any option; the select is
 * then left untouched for the user to set.
 *
 * Tokens for sub-elements are available from webform submissions. Available
 * sub-elements: address_1, address_2, town_city, postcode, country, uprn.
 * Example token: [webform_submission:values:ELEMENT_ID:country]
 *
 * @FormElement("localgov_forms_international_address_lookup")
 */
class InternationalAddressLookup extends UKAddressLookup {

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element): array {
    $elements = parent::getCompositeElements($element);

    // Move the closing address-entry div from postcode to country so the
    // country field is visually grouped inside js-address-entry-container.
    unset($elements['postcode']['#suffix']);

    $elements['country'] = [
      '#type' => 'select',
      '#title' => t('Country'),
      '#options' => 'country_names',
      '#empty_option' => t('- Select country -'),
      '#suffix' => '</div>',
      '#attributes' => [
        'autocomplete' => 'country-name',
        // Namespaced class so the address select JS can set the country from
        // the geocoded result, mirroring the address line classes added by
        // WebformUKAddress.
        'class' => [
          'localgov-forms-webform-international-address--country',
          'js-localgov-forms-webform-international-address--country',
        ],
      ],
    ];

    $elements['address_lookup']['#attached']['library'][] =
      'localgov_forms/localgov_forms_international_address_lookup';

    return $elements;
  }

}
