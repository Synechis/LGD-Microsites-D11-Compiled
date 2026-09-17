<?php

namespace Drupal\domain_path;

use Drupal\Core\Language\LanguageInterface;
use Drupal\path_alias\AliasManager;

/**
 * The domain path alias manager.
 */
class DomainPathAliasManager extends AliasManager {

  /**
   * The method to determine language.
   *
   * @var string
   */
  protected $method;

  /**
   * The domain path entity.
   *
   * @var \Drupal\domain_path\Entity\DomainPath
   */
  protected $domainPath;

  /**
   * {@inheritdoc}
   */
  public function getPathByAlias($alias, $langcode = NULL) {
    // @phpstan-ignore-next-line
    $active = \Drupal::service('domain.negotiator')->getActiveDomain();
    if ($active) {
      $properties = [
        'alias' => $alias,
        // @phpstan-ignore-next-line
        'domain_id' => \Drupal::service('domain.negotiator')->getActiveDomain()->id(),
      ];
      // @phpstan-ignore-next-line
      $domain_paths = \Drupal::entityTypeManager()->getStorage('domain_path')->loadByProperties($properties);

      // https://git.drupalcode.org/project/drupal/-/blob/9.2.x/core/modules/path_alias/src/PathProcessor/AliasPathProcessor.php#L36
      // didn't pass the $langcode.
      $langcode = $langcode ?: $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
      if ($langcode == NULL) {
        // @todo keep a "zxx -> Not applicable" in record for language
        // negotiation failed?
        // LANGCODE_NOT_SPECIFIED = 'und'
        $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;
        // Return the first record when language negotiation failed at this
        // moment.
        $this->domainPath = reset($domain_paths);
        if ($this->domainPath) {
          return $this->domainPath->getSource();
        }
      }
      else {
        foreach ($domain_paths as $domain_path) {
          if ($domain_path->getLanguageCode() == $langcode) {
            $this->domainPath = $domain_path;
            return $this->domainPath->getSource();
          }
        }
      }
    }
    return parent::getPathByAlias($alias, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function getAliasByPath($path, $langcode = NULL) {
    // @phpstan-ignore-next-line
    $config = \Drupal::config('domain_path.settings');
    $this->method = $config->get('language_method') ? $config->get('language_method') : LanguageInterface::TYPE_CONTENT;

    // @phpstan-ignore-next-line
    $active = \Drupal::service('domain.negotiator')->getActiveDomain();
    if ($active) {
      $properties = [
        'source' => $path,
        // @phpstan-ignore-next-line
        'domain_id' => \Drupal::service('domain.negotiator')->getActiveDomain()->id(),
      ];
      // @phpstan-ignore-next-line
      $domain_paths = \Drupal::entityTypeManager()->getStorage('domain_path')->loadByProperties($properties);
      $langcode = $langcode ?: $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
      if ($langcode == NULL) {
        // @todo 'zxx' = active for any language or not?
        $langcode = LanguageInterface::LANGCODE_NOT_APPLICABLE;
      }
      foreach ($domain_paths as $domain_path) {
        if ($domain_path->getLanguageCode() == $langcode) {
          $this->domainPath = $domain_path;
          return $this->domainPath->getAlias();
        }
      }
    }
    return parent::getAliasByPath($path, $langcode);
  }

}
