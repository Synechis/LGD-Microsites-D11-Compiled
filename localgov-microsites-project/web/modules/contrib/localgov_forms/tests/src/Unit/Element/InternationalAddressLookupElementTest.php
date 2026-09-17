<?php

declare(strict_types=1);

namespace Drupal\Tests\localgov_forms\Unit\Element;

use Drupal\Tests\UnitTestCase;
use Drupal\localgov_forms\Element\InternationalAddressLookup;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Unit tests for InternationalAddressLookup form element.
 *
 * @group localgov_forms
 */
final class InternationalAddressLookupElementTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // getCompositeElements() calls t() via the parent chain; provide a stub.
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Returns the composite elements array for inspection.
   */
  private function elements(): array {
    return InternationalAddressLookup::getCompositeElements([]);
  }

  /**
   * Tests that the country element is present in the composite.
   */
  public function testCountryElementIsPresent(): void {
    $this->assertArrayHasKey('country', $this->elements());
  }

  /**
   * Tests that the country element is a select field.
   */
  public function testCountryElementTypeIsSelect(): void {
    $this->assertSame('select', $this->elements()['country']['#type']);
  }

  /**
   * Tests that the country element references the country_names options entity.
   */
  public function testCountryElementUsesCountryNamesWebformOptions(): void {
    $this->assertSame('country_names', $this->elements()['country']['#options']);
  }

  /**
   * Tests that the country element has an empty (prompt) option.
   */
  public function testCountryElementHasEmptyOption(): void {
    $this->assertArrayHasKey('#empty_option', $this->elements()['country']);
  }

  /**
   * Tests that the country element carries the correct autocomplete attribute.
   */
  public function testCountryElementHasAutocompleteAttribute(): void {
    $this->assertSame('country-name', $this->elements()['country']['#attributes']['autocomplete']);
  }

  /**
   * Tests that the country element carries the JS hook class.
   *
   * The address select JavaScript uses this class to populate the country from
   * the geocoded address.
   */
  public function testCountryElementHasJsHookClass(): void {
    $this->assertContains(
      'js-localgov-forms-webform-international-address--country',
      $this->elements()['country']['#attributes']['class'],
    );
  }

  /**
   * Tests that the country element closes the address-entry container div.
   */
  public function testCountryClosesAddressEntryDiv(): void {
    $this->assertSame('</div>', $this->elements()['country']['#suffix']);
  }

  /**
   * Tests that the postcode element no longer carries the closing div suffix.
   */
  public function testPostcodeNoLongerClosesAddressEntryDiv(): void {
    $this->assertArrayNotHasKey('#suffix', $this->elements()['postcode']);
  }

  /**
   * Tests that address_1 still opens the address-entry container div.
   */
  public function testAddress1StillOpensAddressEntryDiv(): void {
    $this->assertStringContainsString('js-address-entry-container', $this->elements()['address_1']['#prefix']);
  }

  /**
   * Tests that country appears after postcode in the composite element order.
   */
  public function testCountryAppearsAfterPostcode(): void {
    $keys = array_keys($this->elements());
    $this->assertGreaterThan(
      array_search('postcode', $keys),
      array_search('country', $keys),
    );
  }

}
