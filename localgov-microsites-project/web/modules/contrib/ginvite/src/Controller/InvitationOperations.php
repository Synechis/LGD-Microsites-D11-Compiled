<?php

namespace Drupal\ginvite\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\ginvite\GroupInvitationManager;
use Drupal\ginvite\Plugin\Group\Relation\GroupInvitation;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupMembership;
use Drupal\group\Entity\GroupRelationshipInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles Accept/Decline operations and Access check for them.
 */
class InvitationOperations extends ControllerBase {

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Group invitation manager.
   *
   * @var \Drupal\ginvite\GroupInvitationManager
   */
  protected $groupInvitationManager;

  /**
   * InvitationOperations constructor.
   *
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\ginvite\GroupInvitationManager $group_invitation_manager
   *   Group invitation manager.
   */
  public function __construct(
    EntityFormBuilderInterface $entity_form_builder,
    MessengerInterface $messenger,
    GroupInvitationManager $group_invitation_manager,
  ) {
    $this->entityFormBuilder = $entity_form_builder;
    $this->messenger = $messenger;
    $this->groupInvitationManager = $group_invitation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.form_builder'),
      $container->get('messenger'),
      $container->get('ginvite.group_invitation_manager')
    );
  }

  /**
   * Create user membership and change invitation status.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   HTTP request.
   * @param \Drupal\group\Entity\GroupRelationshipInterface $group_relationship
   *   Invitation entity.
   *
   * @return array
   *   The processed form for the given entity and operation.
   */
  public function accept(Request $request, GroupRelationshipInterface $group_relationship) {
    $group = $group_relationship->getGroup();
    $group_id = $group->id();

    $invitation_plugin_configuration = $group_relationship->getPlugin()->getConfiguration();

    $group_membership = $this->groupInvitationManager->createMember($group_relationship);

    if (!empty($invitation_plugin_configuration['invitation_bypass_form']) && $invitation_plugin_configuration['invitation_bypass_form'] === TRUE) {
      // Save the membership immediately.
      $group_membership->save();

      // Set a message.
      $this->messenger()->addStatus($this->t('You have accepted the invitation.'));

      // Try to honor the destination parameter, fallback to the group route.
      if ($request->query->has('destination')) {
        $destination = $request->attributes->get('destination');
        try {
          $path = Url::fromUserInput($destination)->setAbsolute()->toString();
          return new RedirectResponse($path);
        }
        catch (\InvalidArgumentException $e) {
          // We will redirect user later if it failed.
        }
      }

      // Redirect the user to its new group.
      return $this->redirect('entity.group.canonical', ['group' => $group_id]);
    }
    else {
      // Call "join group" form here, and allow user to fill
      // additional fields if there are any.
      return $this->entityFormBuilder->getForm($group_membership, 'group-join');
    }
  }

  /**
   * Decline invitation. Change invitation status.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   HTTP request.
   * @param \Drupal\group\Entity\GroupRelationshipInterface $group_relationship
   *   Invitation entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object that may be returned by the controller.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function decline(Request $request, GroupRelationshipInterface $group_relationship) {
    $group_relationship->set('invitation_status', GroupInvitation::INVITATION_REJECTED)->save();
    $group_bundle = $group_relationship->getGroup()->getGroupType()->label();
    $this->messenger->addMessage($this->t('You have declined the @group_bundle invitation.', ['@group_bundle' => $group_bundle]));

    if ($request->query->has('destination')) {
      $destination = $request->attributes->get('destination');
      try {
        $path = Url::fromUserInput($destination)->setAbsolute()->toString();
        return new RedirectResponse($path);
      }
      catch (\InvalidArgumentException $e) {
        // We will redirect user later if it failed.
      }
    }

    return $this->redirect('user.page');
  }

  /**
   * Renders title for the group invite member route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Rendered translatable title.
   */
  public function invitationTitle(GroupInterface $group) {
    $title = $this->t('Invite members');

    if (NULL !== $group->label()) {
      $title = $this->t('Invite members to group: @group_title', ['@group_title' => $group->label()]);
    }

    return $title;
  }

  /**
   * Checks if this current has access to update invitation.
   *
   * @param \Drupal\group\Entity\GroupRelationshipInterface $group_relationship
   *   Invitation entity.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access check result.
   */
  public function checkAccess(GroupRelationshipInterface $group_relationship) {
    // Anonymous users cannot accept/decline invitations.
    // Invitations for non-existent users have entity_id = 0, which would
    // incorrectly match all anonymous visitors.
    if ($this->currentUser()->isAnonymous()) {
      $cacheability = new CacheableMetadata();
      $cacheability->addCacheContexts(['user.roles:anonymous']);
      return AccessResult::neutral()->addCacheableDependency($cacheability);
    }

    $invited_user_id = $group_relationship->getEntityId();
    $group = $group_relationship->getGroup();

    // We handle only group invitations.
    if ($group_relationship->getPluginId() !== 'group_invitation') {
      return AccessResult::neutral();
    }

    // Plugin is not installed.
    if (!$group->getGroupType()->hasPlugin('group_invitation')) {
      return AccessResult::neutral();
    }

    $current_state = $group_relationship->invitation_status->value;

    // Only allow user accept/decline own invitations.
    if ($invited_user_id == $this->currentUser()->id() && (int) $current_state === GroupInvitation::INVITATION_PENDING && empty(GroupMembership::loadSingle($group, $this->currentUser()))) {
      return AccessResult::allowed();
    }

    return AccessResult::neutral();
  }

}
