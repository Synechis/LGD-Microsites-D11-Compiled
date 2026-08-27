<?php

namespace Drupal\ginvite\EventSubscriber;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\ginvite\Event\InvitationBaseEvent;
use Drupal\ginvite\Event\UserLoginWithInvitationEvent;
use Drupal\ginvite\Event\UserRegisteredFromInvitationEvent;
use Drupal\ginvite\GroupInvitationLoader;
use Drupal\ginvite\GroupInvitationManager;
use Drupal\ginvite\Plugin\Group\Relation\GroupInvitation;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Ginvite module event subscriber.
 *
 * @package Drupal\ginvite\EventSubscriber
 */
class GinviteSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Group invitations loader.
   *
   * @var \Drupal\ginvite\GroupInvitationLoader
   */
  protected $groupInvitationLoader;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Group invitation manager.
   *
   * @var \Drupal\ginvite\GroupInvitationManager
   */
  protected $groupInvitationManager;

  /**
   * The current user's account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Constructs GinviteSubscriber.
   *
   * @param \Drupal\ginvite\GroupInvitationLoader $invitation_loader
   *   Invitations loader service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger factory service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\ginvite\GroupInvitationManager $group_invitation_manager
   *   Group invitation manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   Current path.
   */
  public function __construct(
    GroupInvitationLoader $invitation_loader,
    MessengerInterface $messenger,
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactoryInterface $config_factory,
    GroupInvitationManager $group_invitation_manager,
    AccountInterface $current_user,
    CurrentPathStack $current_path,
  ) {
    $this->groupInvitationLoader = $invitation_loader;
    $this->messenger = $messenger;
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
    $this->groupInvitationManager = $group_invitation_manager;
    $this->currentUser = $current_user;
    $this->currentPath = $current_path;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[KernelEvents::REQUEST][] = ['notifyAboutPendingInvitations'];
    $events[UserRegisteredFromInvitationEvent::EVENT_NAME][] = ['unblockInvitedUsers'];
    $events[UserRegisteredFromInvitationEvent::EVENT_NAME][] = ['autoAcceptGroupInvitation'];
    $events[UserLoginWithInvitationEvent::EVENT_NAME][] = ['autoAcceptGroupInvitation'];
    return $events;
  }

  /**
   * Notify user about Pending invitations.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The RequestEvent to process.
   */
  public function notifyAboutPendingInvitations(RequestEvent $event) {
    // Skip specific paths.
    if ($this->pathFilter()) {
      return;
    }

    // Skip for AJAX requests.
    if ($event->getRequest()->isXmlHttpRequest()) {
      return;
    }

    if ($event->getRequest()->getRequestFormat() !== 'html') {
      return;
    }

    // Skip anonymous users.
    if ($this->currentUser->isAnonymous()) {
      return;
    }

    // Exclude routes where this info is redundant or will generate a
    // misleading extra message on the next request.
    $config = $this->configFactory->get('ginvite.pending_invitations_warning');
    $route = $event->getRequest()->attributes->get('_route');
    if (empty($route) || in_array($route, $config->get('excluded_routes') ?? [], TRUE) || empty($config->get('warning_message'))) {
      return;
    }

    if (empty($this->groupInvitationLoader->getUserInvitations($this->currentUser))) {
      return;
    }

    $destination = Url::fromRoute('view.my_invitations.page_1')->toString();
    $this->messenger->addMessage(new FormattableMarkup($config->get('warning_message'), ['@my_invitations_url' => $destination]), 'warning');
  }

  /**
   * Unblock users when they are coming from pending invitations.
   *
   * @param \Drupal\ginvite\Event\UserRegisteredFromInvitationEvent $event
   *   The UserRegisteredFromInvitationEvent to process.
   */
  public function unblockInvitedUsers(UserRegisteredFromInvitationEvent $event) {
    $invitation = $event->getGroupInvitation();
    $plugin_configuration = $invitation->getGroup()->getGroupType()->getPlugin('group_invitation')->getConfiguration();
    if (empty($plugin_configuration['unblock_invitees'])) {
      return;
    }

    $invited_user = $invitation->getUser();
    if (!$invited_user->isActive()) {
      $invited_user->activate();
      $invited_user->save();
      $this->messenger->addMessage($this->t('User %user unblocked as it comes from an invitation', ['%user' => $invited_user->getDisplayName()]));
      $this->loggerFactory->get('ginvite')->notice($this->t('User %user unblocked as it comes from an invitation', ['%user' => $invited_user->getDisplayName()]));
    }
  }

  /**
   * Auto Accept Group Invitations from the ginvite module.
   *
   * @param \Drupal\ginvite\Event\InvitationBaseEvent $event
   *   The InvitationBaseEvent to process.
   */
  public function autoAcceptGroupInvitation(InvitationBaseEvent $event) {
    $invitation = $event->getGroupInvitation();
    $group_relationship = $invitation->getGroupRelationship();

    $plugin_configuration = $group_relationship->getPlugin()->getConfiguration();
    if (empty($plugin_configuration['autoaccept_invitees'])) {
      return;
    }

    // Set the status of the invitation to accepted and save it.
    $group_relationship->set('invitation_status', GroupInvitation::INVITATION_ACCEPTED);
    $group_relationship->save();

    $group_membership = $this->groupInvitationManager->createMember($group_relationship);
    if ($group_membership->isNew()) {
      $group_membership->save();
    }

  }

  /**
   * Filter file and media paths.
   *
   * @return bool
   *   True if path is to be filtered out, false otherwise.
   */
  protected function pathFilter(): bool {
    $path = ltrim($this->currentPath->getPath(), '/');

    $paths_to_filter = [
      'sites/default',
      'system/files',
      'media/oembed',
      'themes',
      'core',
      'modules',
      'profiles',
      'libraries',
      'jsonapi',
      'graphql',
      'api',
      '.well-known',
    ];

    foreach ($paths_to_filter as $prefix) {
      if (str_starts_with($path, $prefix)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
