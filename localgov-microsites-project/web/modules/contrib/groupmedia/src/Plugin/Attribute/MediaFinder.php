<?php

namespace Drupal\groupmedia\Plugin\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a media finder for plugin discovery.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class MediaFinder extends Plugin {

  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $label = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly array $field_types = [],
    public readonly ?string $element = NULL,
  ) {}

}
