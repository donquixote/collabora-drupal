<?php

namespace Drupal\collabora_online\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Hello' Block.
 *
 * @Block(
 *   id = "cool_block",
 *   admin_label = @Translation("Cool block"),
 *   category = @Translation("Hello from Collabora"),
 * )
 */
class CoolBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => $this->t('Hello from Collabora!'),
    ];
  }

}

?>