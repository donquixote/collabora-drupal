<?php

declare(strict_types=1);

namespace Drupal\collabora_online_group\Plugin\Group\RelationHandler;

use Drupal\groupmedia\Plugin\Group\RelationHandler\GroupMediaPermissionProvider;
use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface;

/**
 * Provides group permissions for Collabora.
 */
class CollaboraPermissionProvider extends GroupMediaPermissionProvider {

  /**
   *  Constructs a new CollaboraPermissionProvider.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface $groupMediaPermissionProvider
   *   The original access control handler.
   * @param \Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface $parent
   *   The parent access control handler.
   */
  public function __construct(
    protected PermissionProviderInterface $groupMediaPermissionProvider,
    PermissionProviderInterface $parent
  ) {
    parent::__construct($parent);
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions(): array {
    $permissions = $this->parent->buildPermissions();

    /** @see \Drupal\group\Plugin\Group\RelationHandlerDefault\PermissionProvider::buildPermissions() */
    $provider_chain = $this->groupRelationTypeManager()->getPermissionProvider($this->pluginId);

    // Add Collabora permissions.
    $prefix = 'Entity:';
    if ($name = $provider_chain->getPermission('preview in collabora', 'entity')) {
      $permissions[$name] = $this->buildPermission("$prefix Preview %entity_type in collabora");
    }
    if ($name = $provider_chain->getPermission('edit in collabora', 'entity')) {
      $permissions[$name] = $this->buildPermission("$prefix Edit any %entity_type in collabora");
    }
    if ($name = $provider_chain->getPermission('edit in collabora', 'entity', 'own')) {
      $permissions[$name] = $this->buildPermission("$prefix Edit own %entity_type in collabora");
    }

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermission($operation, $target, $scope = 'any'): bool|string {
    if ($target === 'entity') {
      switch ($operation) {
        case 'preview in collabora':
          return "preview $this->pluginId in collabora";

        case 'edit in collabora':
          return $this->getCollaboraEditPermission($scope);
      }
    }

    // Since we are overriding decorated service method, we call the original
    // method too.
    return $this->groupMediaPermissionProvider->getPermission($operation, $target, $scope);
  }

  /**
   * Gets the name of the edit permission in Collabora.
   *
   * @param string $scope
   *   (optional) Whether the 'any' or 'own' permission name should be returned.
   *   Defaults to 'any'.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  protected function getCollaboraEditPermission($scope = 'any'): bool|string {
    if ($this->definesEntityPermissions) {
      if ($this->implementsOwnerInterface || $scope === 'any') {
        return "edit $scope $this->pluginId in collabora";
      }
    }

    return FALSE;
  }
}
