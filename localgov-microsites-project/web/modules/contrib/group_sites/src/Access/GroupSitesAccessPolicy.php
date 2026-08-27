<?php

namespace Drupal\group_sites\Access;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flexible_permissions\PermissionCalculatorAlterInterfaceV2;
use Drupal\flexible_permissions\PermissionCalculatorInterface;
use Drupal\flexible_permissions\RefinableCalculatedPermissions;
use Drupal\flexible_permissions\RefinableCalculatedPermissionsInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\group_sites\GroupSitesAccessPolicyException;
use Drupal\group_sites\GroupSitesAdminModeInterface;
use Drupal\group_sites\GroupSitesNegotiator;
use Psr\Container\ContainerInterface;

/**
 * Access policy that runs configured "site" or "no site" access policies.
 */
class GroupSitesAccessPolicy implements PermissionCalculatorInterface, PermissionCalculatorAlterInterfaceV2 {

  use StringTranslationTrait;

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected GroupSitesNegotiator|ContextRepositoryInterface $negotiator,
    protected GroupSitesAdminModeInterface $adminMode,
    protected ContainerInterface $locator,
  ) {
    if ($this->negotiator instanceof ContextRepositoryInterface) {
      @trigger_error('Calling ' . __METHOD__ . '() with a $negotiator argument as \Drupal\Core\Plugin\Context\ContextRepositoryInterface instead of \Drupal\group_sites\GroupSitesNegotiator is deprecated in group_sites:1.1.0 and will be unsupported in group_sites:2.0.0. See https://www.drupal.org/node/3486356', E_USER_DEPRECATED);
      $this->negotiator = \Drupal::service('group_sites.negotiator');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account, $scope) {
    return (new RefinableCalculatedPermissions())->addCacheContexts($this->getPersistentCacheContexts($scope));
  }

  /**
   * {@inheritdoc}
   */
  public function alterPermissions(AccountInterface $account, $scope, RefinableCalculatedPermissionsInterface $calculated_permissions) {
    $supported_scopes = [
      PermissionScopeInterface::INDIVIDUAL_ID,
      PermissionScopeInterface::OUTSIDER_ID,
      PermissionScopeInterface::INSIDER_ID,
    ];
    if (!in_array($scope, $supported_scopes, TRUE)) {
      return;
    }

    if ($this->adminMode->isActive()) {
      return;
    }

    $cacheable_metadata = new CacheableMetadata();
    if ($group = $this->negotiator->getActiveGroup($cacheable_metadata)) {
      $this->getSiteAccessPolicy()->alterPermissions($group, $account, $scope, $calculated_permissions);
    }
    else {
      $this->getNoSiteAccessPolicy()->alterPermissions($account, $scope, $calculated_permissions);
    }
    $calculated_permissions->addCacheableDependency($cacheable_metadata);
  }

  /**
   * {@inheritdoc}
   */
  public function getPersistentCacheContexts($scope) {
    $supported_scopes = [
      PermissionScopeInterface::INDIVIDUAL_ID,
      PermissionScopeInterface::OUTSIDER_ID,
      PermissionScopeInterface::INSIDER_ID,
    ];
    if (!in_array($scope, $supported_scopes, TRUE)) {
      return [];
    }

    // We only add the flag here as the context actually sets the other cache
    // contexts. This is a perfect use case for cache redirects where we first
    // hand only a single cache context to VariationCache, but depending on
    // embedded context logic, we may end up adding more in alterPermissions().
    return ['user.in_group_sites_admin_mode'];
  }

  /**
   * Gets the configured access policy for when there is no site.
   *
   * @return \Drupal\group_sites\Access\GroupSitesNoSiteAccessPolicyInterface
   *   The access policy.
   */
  protected function getNoSiteAccessPolicy() {
    $access_policy_id = $this->configFactory->get('group_sites.settings')->get('no_site_access_policy');
    $access_policy = $this->locator->get($access_policy_id);

    if (!$access_policy instanceof GroupSitesNoSiteAccessPolicyInterface) {
      throw new GroupSitesAccessPolicyException($access_policy_id, GroupSitesNoSiteAccessPolicyInterface::class);
    }

    return $access_policy;
  }

  /**
   * Gets the configured access policy for when there is a site.
   *
   * @return \Drupal\group_sites\Access\GroupSitesSiteAccessPolicyInterface
   *   The access policy.
   */
  protected function getSiteAccessPolicy() {
    $access_policy_id = $this->configFactory->get('group_sites.settings')->get('site_access_policy');
    $access_policy = $this->locator->get($access_policy_id);

    if (!$access_policy instanceof GroupSitesSiteAccessPolicyInterface) {
      throw new GroupSitesAccessPolicyException($access_policy_id, GroupSitesSiteAccessPolicyInterface::class);
    }

    return $access_policy;
  }

}
