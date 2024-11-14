<?php

declare(strict_types=1);

namespace Drupal\Tests\collabora_online_group\Traits;

use Drupal\group\Entity\GroupRelationshipTypeInterface;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Provides a method to create a relation between a group type and a plugin.
 */
trait GroupRelationTrait {

  /**
   * Creates a relation between a group type plugin and a plugin.
   *
   * Wrapper to support group 2.x and 3.x.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type
   * @param string $plugin_id
   *   The plugin.
   * @param array $values
   *   Values for the relation.
   *
   * @return \Drupal\group\Entity\GroupRelationshipTypeInterface
   *   New entity.
   */
  protected function createPluginRelation(GroupTypeInterface $group_type, string $plugin_id, array $values = []): GroupRelationshipTypeInterface {
    $entity_type_id = 'group_relationship_type';

    // Fallback for older versions.
     if ($this->entityTypeManager()->getDefinition($entity_type_id, FALSE) === NULL) {
      $entity_type_id = 'group_content_type';
    }

    $entity = $this->entityTypeManager()
      ->getStorage($entity_type_id)
      ->createFromPlugin($group_type, $plugin_id, $values);
    $entity->save();

    return $entity;
  }

}
