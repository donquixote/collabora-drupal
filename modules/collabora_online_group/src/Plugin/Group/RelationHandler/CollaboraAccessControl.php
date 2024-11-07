<?php

declare(strict_types=1);

namespace Drupal\collabora_online_group\Plugin\Group\RelationHandler;

use Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlTrait;

/**
 * Checks access for Collabora.
 */
class CollaboraAccessControl implements AccessControlInterface {

  use AccessControlTrait;

  /**
   * Constructs a new CollaboraAccessControl.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface $parent
   *   The parent access control handler.
   */
  public function __construct(AccessControlInterface $parent) {
    $this->parent = $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsOperation($operation, $target): bool {
    $allowed_operations = [
      'edit in collabora',
      'preview in collabora'
    ];

    if (in_array($operation, $allowed_operations) && $target === 'entity') {
      return TRUE;
    }

    return $this->parent->supportsOperation($operation, $target);
  }

}
