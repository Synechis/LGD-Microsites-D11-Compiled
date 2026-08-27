<?php

namespace Drupal\gnode\Access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\node\Access\NodePreviewAccessCheck as CoreNodePreviewAccessCheck;
use Drupal\node\NodeInterface;

/**
 * Determines access to node previews when the node belongs to a group.
 */
class NodePreviewAccessCheck extends CoreNodePreviewAccessCheck {

  /**
   * The decorated core node preview access check service.
   *
   * @var \Drupal\node\Access\NodePreviewAccessCheck
   */
  protected $innerService;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group relation type manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $groupRelationTypeManager;

  /**
   * Stores the tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Constructs an NodePreviewAccessCheck object.
   *
   * @param \Drupal\Core\Routing\Access\AccessInterface $inner_service
   *   The decorated node preview access check service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $group_relation_type_manager
   *   The group relation type manager.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   */
  public function __construct(AccessInterface $inner_service, EntityTypeManagerInterface $entity_type_manager, GroupRelationTypeManagerInterface $group_relation_type_manager, PrivateTempStoreFactory $temp_store_factory) {
      $this->innerService = $inner_service;
      $this->groupRelationTypeManager = $group_relation_type_manager;
      $this->tempStoreFactory = $temp_store_factory;
      parent::__construct($entity_type_manager);
  }

  /**
   * Checks access to the grouped node preview page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\node\NodeInterface $node_preview
   *   The node that is being previewed.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, NodeInterface $node_preview) {
    if (!$node_preview->isNew()) {
      return $this->innerService->access($account, $node_preview);
    }

    $store = $this->tempStoreFactory->get('node_preview');
    $form_state = $store->get($node_preview->uuid());
    $group = $form_state ? $form_state->get('group') : NULL;
    if ($form_state && $group instanceof GroupInterface && ($plugin_id = $form_state->get('group_relation'))) {
      return $this->groupRelationTypeManager
        ->getAccessControlHandler($plugin_id)
        ->entityCreateAccess($group, $account, TRUE);
    }

    return $this->innerService->access($account, $node_preview);
  }
}
