<?php

/**
 * @file
 * Post update functions for LocalGov News.
 */

use Drupal\pathauto\Entity\PathautoPattern;
use Drupal\pathauto\PathautoPatternInterface;

/**
 * Update pathauto patterns to avoid inclusion of subdirectory, language, etc.
 */
function localgov_news_post_update_pathauto_parent(): void {
  $alias = PathautoPattern::load('localgov_news');
  if ($alias instanceof PathautoPatternInterface &&
    $alias->getPattern() == '[node:localgov_newsroom:entity:url:relative]/[node:localgov_news_date:date:html_year]/[node:title]'
  ) {
    $alias->setPattern('[node:localgov_newsroom:entity:url:path]/[node:localgov_news_date:date:html_year]/[node:title]');
    $alias->save();
  }
}
