<?php

namespace Drupal\domain\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Deletes the container if domain www_prefix setting has changed.
 */
class ConfigSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Validates domain records during configuration import.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   The configuration event.
   */
  public function onConfigImporterValidate(ConfigImporterEvent $event) {
    $importer = $event->getConfigImporter();
    $comparer = $importer->getStorageComparer();

    // Get all changes across create, update, rename, and delete operations.
    $changelist = [];
    foreach ($comparer->getChangelist() as $list) {
      $changelist = array_merge($changelist, $list);
    }

    // Check if any domain records are in the changelist.
    $has_domain_changes = FALSE;
    foreach ($changelist as $config_name) {
      if (str_starts_with($config_name, 'domain.record.')) {
        $has_domain_changes = TRUE;
        break;
      }
    }

    // If no domain records are being updated, created, or deleted, do nothing.
    if (!$has_domain_changes) {
      return;
    }

    $source_storage = $comparer->getSourceStorage();
    $target_storage = $comparer->getTargetStorage();

    $domain_ids = [];

    // Get all domain records from the source storage.
    foreach ($source_storage->listAll('domain.record.') as $name) {
      $data = $source_storage->read($name);
      if (isset($data['domain_id'])) {
        $domain_id = $data['domain_id'];
        if (isset($domain_ids[$domain_id])) {
          $importer->logError($this->t(
            'The domain_id @id is already used by another domain record (@name).',
            ['@id' => $domain_id, '@name' => substr($domain_ids[$domain_id], strlen('domain.record.'))],
          ));
        }
        $domain_ids[$domain_id] = $name;
      }
    }

    // Also check against existing domain records that are NOT being updated.
    foreach ($target_storage->listAll('domain.record.') as $name) {
      // If this record is in the source storage, it's being updated or deleted,
      // so we already handled it or it will be gone.
      if (!$source_storage->exists($name)) {
        $data = $target_storage->read($name);
        if (isset($data['domain_id'])) {
          $domain_id = $data['domain_id'];
          if (isset($domain_ids[$domain_id])) {
            $importer->logError($this->t(
              'The domain_id @id is already used by another domain record (@name).',
              ['@id' => $domain_id, '@name' => substr($domain_ids[$domain_id], strlen('domain.record.'))],
            ));
          }
          $domain_ids[$domain_id] = $name;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[ConfigEvents::IMPORT_VALIDATE][] = ['onConfigImporterValidate', 0];
    return $events;
  }

}
