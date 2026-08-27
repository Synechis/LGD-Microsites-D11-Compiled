<?php

namespace Drupal\groupmedia\Plugin\MediaFinder;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\groupmedia\Plugin\Attribute\MediaFinder;

/**
 * Tracks usage of drupal-media tags in wysiwyg fields.
 */
#[MediaFinder(
  id: 'groupmedia_media_embed',
  label: new TranslatableMarkup('Groupmedia: Media WYSIWYG Embed (Core)'),
  description: new TranslatableMarkup("Tracks relationships created with Core's 'Embed media' filter in formatted text fields."),
  field_types: [
    'text',
    'text_long',
    'text_with_summary',
  ],
  element: 'drupal-media'
)]
class MediaEmbed extends TextFieldEmbedBase {}
