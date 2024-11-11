<?php

declare(strict_types=1);

namespace Drupal\collabora_online_group\Plugin\Group\RelationHandler;

use Drupal\groupmedia\Plugin\Group\RelationHandler\GroupMediaPermissionProvider;

/**
 * Provides Collabora permissions for group.
 */
class CollaboraPermissionProvider extends GroupMediaPermissionProvider {

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
          if (
            $this->definesEntityPermissions &&
            ($this->implementsOwnerInterface || $scope === 'any')
          ) {
              return "edit $scope $this->pluginId in collabora";
          }

          return FALSE;
      }
    }

    return $this->parent->getPermission($operation, $target, $scope);
  }

}
