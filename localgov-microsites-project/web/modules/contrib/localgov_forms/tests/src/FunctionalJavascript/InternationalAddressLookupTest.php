<?php

declare(strict_types=1);

namespace Drupal\Tests\localgov_forms\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the international address lookup composite element.
 */
class InternationalAddressLookupTest extends WebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['localgov_forms', 'localgov_forms_test'];

  /**
   * As it says on the tin.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that selecting an address fills in the country along with the lines.
   */
  public function testCountryIsPopulatedFromLookup(): void {

    $page           = $this->getSession()->getPage();
    $session_assert = $this->assertSession();

    $this->drupalGet('/webform/international_address_test');
    $session_assert->waitForElementVisible('css', '#edit-address-address-lookup-address-search-address-searchstring');

    $search_textfield = $page->find('css', '#edit-address-address-lookup-address-search-address-searchstring');
    $this->assertNotEmpty($search_textfield);
    $search_textfield->setValue('BN1 1JE');

    $search_btn = $page->find('css', '#edit-address-address-lookup-address-search-address-actions-address-searchbutton');
    $this->assertNotEmpty($search_btn);
    $search_btn->click();
    $session_assert->waitForElementVisible('css', '[data-drupal-selector=edit-address-address-lookup-address-select-address-select-list]');

    $address_dropdown = $page->find('css', '[data-drupal-selector=edit-address-address-lookup-address-select-address-select-list]');
    $this->assertNotEmpty($address_dropdown);

    // Select the one and only address option from the address dropdown.
    $address_dropdown->selectOption('000022062038');
    $session_assert->waitForElementVisible('css', '#edit-address-address-1');

    $address1_textfield = $page->find('css', '#edit-address-address-1');
    $this->assertNotEmpty($address1_textfield);
    $this->assertEquals('Brighton & Hove City Council, Bartholomew House', $address1_textfield->getValue());

    // The mock geocoder returns "United Kingdom", which matches an option of
    // the country_names options entity.
    $country_select = $page->find('css', '#edit-address-country');
    $this->assertNotEmpty($country_select);
    $this->assertEquals('United Kingdom', $country_select->getValue());
  }

  /**
   * Tests that resetting the address clears the country selection.
   */
  public function testCountryIsClearedOnReset(): void {

    $page           = $this->getSession()->getPage();
    $session_assert = $this->assertSession();

    $this->drupalGet('/webform/international_address_test');
    $session_assert->waitForElementVisible('css', '#edit-address-address-lookup-address-search-address-searchstring');

    $search_textfield = $page->find('css', '#edit-address-address-lookup-address-search-address-searchstring');
    $search_textfield->setValue('BN1 1JE');
    $page->find('css', '#edit-address-address-lookup-address-search-address-actions-address-searchbutton')->click();
    $session_assert->waitForElementVisible('css', '[data-drupal-selector=edit-address-address-lookup-address-select-address-select-list]');

    $page->find('css', '[data-drupal-selector=edit-address-address-lookup-address-select-address-select-list]')
      ->selectOption('000022062038');
    $session_assert->waitForElementVisible('css', '#edit-address-country');

    // The reset button is unhidden by address_change.js after a search.
    $reset_btn = $session_assert->waitForElementVisible('css', '.js-reset-address');
    $this->assertNotEmpty($reset_btn);
    $reset_btn->click();

    $country_select = $page->find('css', '#edit-address-country');
    $this->assertNotEmpty($country_select);
    $this->assertEquals('', $country_select->getValue());
  }

}
