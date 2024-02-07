<?php
namespace Drupal\collabora_online\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the Example module.
 */
class ViewerController extends ControllerBase {

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function view() {
    return [
      '#markup' => 'Hello from Collabora',
    ];
  }

}

?>