<?php

namespace Drupal\groupmedia\Plugin\MediaFinder;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\groupmedia\Plugin\Attribute\MediaFinder;

/**
 * Tracks usage of entities related in text fields.
 */
#[MediaFinder(
  id: 'groupmedia_entity_embed',
  label: new TranslatableMarkup('Groupmedia: Entity Embed'),
  description: new TranslatableMarkup("Tracks relationships created with 'Entity Embed' in formatted text fields."),
  field_types: [
    'text',
    'text_long',
    'text_with_summary',
  ],
  element: 'drupal-media'
)]
class EntityEmbed extends TextFieldEmbedBase {}
