<?php

namespace Drupal\domain_path\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\path\Form\PathFilterForm;

/**
 * Provides a filter form for the Domain Path aliases listing.
 *
 * @phpstan-ignore-next-line
 */
class DomainPathFilterForm extends PathFilterForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.domain_path.collection', [], [
      'query' => ['search' => trim($form_state->getValue('filter'))],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.domain_path.collection');
  }

}
