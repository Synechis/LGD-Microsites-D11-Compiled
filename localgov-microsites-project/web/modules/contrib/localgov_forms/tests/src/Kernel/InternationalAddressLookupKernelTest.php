<?php

declare(strict_types=1);

namespace Drupal\Tests\localgov_forms\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\localgov_forms\Plugin\WebformElement\InternationalAddressLookup;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Kernel tests for the InternationalAddressLookup element.
 *
 * Tests require real webform entities so that preSave() can call
 * $webform_submission->getWebform()->getElement() and get a live element
 * definition back.
 *
 * @group localgov_forms
 * @runTestsInSeparateProcesses
 *
 * PHPUnit 10.2+ attributes below; not available under the "previous major"
 * job's older PHPUnit — docblock annotations above cover that job instead.
 * Remove this ignore once previous-major support drops that PHPUnit version.
 * @phpstan-ignore-next-line
 */
#[RunTestsInSeparateProcesses, Group('localgov_forms')]
class InternationalAddressLookupKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'path',
    'path_alias',
    'geocoder',
    'webform',
    'localgov_forms',
  ];

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

    $webform = Webform::create([
      'id' => 'international_address_test',
      'title' => 'International address test',
      'elements' => "delivery_address:\n  '#type': localgov_forms_international_address_lookup\n  '#title': 'Delivery address'",
    ]);
    $webform->save();
  }

  /**
   * Tests that preSave() strips transients and preserves country.
   *
   * Uses newInstanceWithoutConstructor() so no geocoder service is needed;
   * preSave() only calls getData/getWebform/setData on the submission.
   */
  public function testPreSaveStripsTransientsAndPreservesCountry(): void {
    $plugin = (new \ReflectionClass(InternationalAddressLookup::class))
      ->newInstanceWithoutConstructor();

    $submission = WebformSubmission::create(['webform_id' => 'international_address_test']);
    $submission->setData([
      'delivery_address' => [
        'address_1' => '10 Downing Street',
        'address_2' => '',
        'town_city' => 'London',
        'postcode' => 'SW1A 2AA',
        'country' => 'France',
        'uprn' => '100023336956',
        'address_lookup' => ['search' => 'SW1A'],
        'lat' => '51.503',
        'lng' => '-0.127',
        'ward' => 'Westminster',
      ],
    ]);

    $element = [];
    $plugin->preSave($element, $submission);

    $data = $submission->getData()['delivery_address'];
    $this->assertSame('France', $data['country']);
    $this->assertSame('10 Downing Street', $data['address_1']);
    $this->assertSame('100023336956', $data['uprn']);
    $this->assertArrayNotHasKey('address_lookup', $data);
    $this->assertArrayNotHasKey('lat', $data);
    $this->assertArrayNotHasKey('lng', $data);
    $this->assertArrayNotHasKey('ward', $data);
  }

  /**
   * Tests that the country sub-element token resolves from submission data.
   *
   * Uses webform's token integration to assert that
   * [webform_submission:values:delivery_address:country] returns the stored
   * country value.
   */
  public function testCountryTokenResolves(): void {
    $submission = WebformSubmission::create(['webform_id' => 'international_address_test']);
    $submission->setData([
      'delivery_address' => [
        'address_1' => '10 Downing Street',
        'address_2' => '',
        'town_city' => 'London',
        'postcode' => 'SW1A 2AA',
        'country' => 'France',
        'uprn' => '',
      ],
    ]);

    $token = \Drupal::token()->replace(
      '[webform_submission:values:delivery_address:country]',
      ['webform_submission' => $submission],
    );
    $this->assertSame('France', $token);
  }

}
