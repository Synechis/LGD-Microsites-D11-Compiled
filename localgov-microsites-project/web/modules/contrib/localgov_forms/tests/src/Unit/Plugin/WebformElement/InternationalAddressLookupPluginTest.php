<?php

declare(strict_types=1);

namespace Drupal\Tests\localgov_forms\Unit\Plugin\WebformElement;

use Drupal\Tests\UnitTestCase;
use Drupal\localgov_forms\Plugin\WebformElement\InternationalAddressLookup;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Unit tests for InternationalAddressLookup webform element plugin.
 *
 * @group localgov_forms
 */
final class InternationalAddressLookupPluginTest extends UnitTestCase {

  /**
   * Returns a real plugin instance without invoking the constructor.
   *
   * Suitable for methods with no injected-service dependencies (e.g. preSave).
   */
  private function makePlugin(): InternationalAddressLookup {
    return (new \ReflectionClass(InternationalAddressLookup::class))
      ->newInstanceWithoutConstructor();
  }

  /**
   * Returns a plugin mock where getValue() returns the given array.
   *
   * Used for testing formatTextItemValue() and formatHtmlItemValue() without
   * requiring the full webform element service stack.
   *
   * @param array $value
   *   The address field values to return from getValue().
   */
  private function makePluginWithValue(array $value): InternationalAddressLookup {
    $plugin = $this->getMockBuilder(InternationalAddressLookup::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getValue'])
      ->getMock();
    $plugin->method('getValue')->willReturn($value);
    return $plugin;
  }

  /**
   * Invokes a protected method on the given object and returns the result.
   *
   * @param object $object
   *   The object to invoke the method on.
   * @param string $method
   *   The protected method name.
   * @param array $args
   *   Arguments to pass to the method.
   */
  private function callProtected(object $object, string $method, array $args): mixed {
    $ref = new \ReflectionMethod($object, $method);
    $ref->setAccessible(TRUE);
    return $ref->invokeArgs($object, $args);
  }

  /**
   * Runs preSave() and returns the data passed to setData().
   *
   * @param \Drupal\localgov_forms\Plugin\WebformElement\InternationalAddressLookup $plugin
   *   The plugin instance to test.
   * @param array $initialData
   *   The submission data to set up on the mock submission.
   * @param string $elementType
   *   The '#type' that getElement() reports for all submission keys.
   */
  private function capturePreSave(InternationalAddressLookup $plugin, array $initialData, string $elementType = 'localgov_forms_international_address_lookup'): array {
    $webform = $this->createMock(WebformInterface::class);
    $webform->method('getElement')->willReturn(['#type' => $elementType]);

    $submission = $this->createMock(WebformSubmissionInterface::class);
    $submission->method('getData')->willReturn($initialData);
    $submission->method('getWebform')->willReturn($webform);

    $saved = NULL;
    $submission->expects($this->once())
      ->method('setData')
      ->willReturnCallback(static function (array $data) use (&$saved): void {
        $saved = $data;
      });

    $element = [];
    $plugin->preSave($element, $submission);
    return $saved;
  }

  /**
   * Tests that preSave() strips the address lookup widget from submission data.
   */
  public function testPreSaveStripsAddressLookupWidget(): void {
    $saved = $this->capturePreSave($this->makePlugin(), [
      'delivery_address' => [
        'address_lookup' => ['search' => 'SW1A'],
        'address_1' => '10 Downing Street',
        'country' => 'United Kingdom',
        'lat' => '51.503',
        'lng' => '-0.127',
        'ward' => 'Westminster',
        'uprn' => '100023336956',
      ],
    ]);
    $this->assertArrayNotHasKey('address_lookup', $saved['delivery_address']);
  }

  /**
   * Tests that preSave() strips lat, lng, and ward from submission data.
   */
  public function testPreSaveStripsGeoFields(): void {
    $saved = $this->capturePreSave($this->makePlugin(), [
      'delivery_address' => [
        'address_1' => '10 Downing Street',
        'address_lookup' => [],
        'country' => 'United Kingdom',
        'lat' => '51.503',
        'lng' => '-0.127',
        'ward' => 'Westminster',
      ],
    ]);
    $addr = $saved['delivery_address'];
    $this->assertArrayNotHasKey('lat', $addr);
    $this->assertArrayNotHasKey('lng', $addr);
    $this->assertArrayNotHasKey('ward', $addr);
  }

  /**
   * Tests that preSave() retains address and country fields on submission data.
   */
  public function testPreSaveRetainsAddressAndCountryFields(): void {
    $saved = $this->capturePreSave($this->makePlugin(), [
      'delivery_address' => [
        'address_1' => '10 Downing Street',
        'address_2' => '',
        'town_city' => 'London',
        'postcode' => 'SW1A 2AA',
        'country' => 'United Kingdom',
        'uprn' => '100023336956',
        'address_lookup' => [],
        'lat' => '51.503',
        'lng' => '-0.127',
        'ward' => 'Westminster',
      ],
    ]);
    $addr = $saved['delivery_address'];
    $this->assertSame('10 Downing Street', $addr['address_1']);
    $this->assertSame('London', $addr['town_city']);
    $this->assertSame('SW1A 2AA', $addr['postcode']);
    $this->assertSame('United Kingdom', $addr['country']);
    $this->assertSame('100023336956', $addr['uprn']);
  }

  /**
   * Tests that preSave() leaves elements of other types untouched.
   */
  public function testPreSaveDoesNotAffectOtherElementTypes(): void {
    $saved = $this->capturePreSave(
      $this->makePlugin(),
      [
        'home_address' => [
          'address_1' => '1 Council House',
          'address_lookup' => ['search' => 'B1'],
          'lat' => '52.483',
        ],
      ],
      'localgov_webform_uk_address',
    );
    $this->assertArrayHasKey('address_lookup', $saved['home_address']);
    $this->assertArrayHasKey('lat', $saved['home_address']);
  }

  /**
   * Tests that formatTextItemValue() assembles a complete address line.
   */
  public function testFormatTextBuildsFullAddressLine(): void {
    $plugin = $this->makePluginWithValue([
      'address_1' => '10 Downing Street',
      'address_2' => '',
      'town_city' => 'London',
      'postcode' => 'SW1A 2AA',
      'country' => 'United Kingdom',
    ]);
    $submission = $this->createMock(WebformSubmissionInterface::class);
    $result = $this->callProtected($plugin, 'formatTextItemValue', [[], $submission]);
    $this->assertSame(['10 Downing Street London SW1A 2AA United Kingdom'], $result);
  }

  /**
   * Tests that formatTextItemValue() omits empty fields without leaving gaps.
   */
  public function testFormatTextSkipsEmptyFields(): void {
    $plugin = $this->makePluginWithValue([
      'address_1' => 'Flat 1',
      'address_2' => '',
      'town_city' => 'Birmingham',
      'postcode' => 'B1 1AA',
      'country' => 'United Kingdom',
    ]);
    $submission = $this->createMock(WebformSubmissionInterface::class);
    [$line] = $this->callProtected($plugin, 'formatTextItemValue', [[], $submission]);
    $this->assertStringNotContainsString('  ', $line);
  }

  /**
   * Tests that formatTextItemValue() returns [] when all values are empty.
   */
  public function testFormatTextReturnsEmptyArrayWhenAllValuesAreEmpty(): void {
    $plugin = $this->makePluginWithValue([
      'address_1' => '',
      'address_2' => '',
      'town_city' => '',
      'postcode' => '',
      'country' => '',
    ]);
    $submission = $this->createMock(WebformSubmissionInterface::class);
    $result = $this->callProtected($plugin, 'formatTextItemValue', [[], $submission]);
    $this->assertSame([], $result);
  }

  /**
   * Tests that formatHtmlItemValue() delegates to formatTextItemValue().
   */
  public function testFormatHtmlMatchesFormatText(): void {
    $plugin = $this->makePluginWithValue([
      'address_1' => '10 Downing Street',
      'address_2' => '',
      'town_city' => 'London',
      'postcode' => 'SW1A 2AA',
      'country' => 'United Kingdom',
    ]);
    $submission = $this->createMock(WebformSubmissionInterface::class);
    $this->assertSame(
      $this->callProtected($plugin, 'formatTextItemValue', [[], $submission]),
      $this->callProtected($plugin, 'formatHtmlItemValue', [[], $submission]),
    );
  }

}
