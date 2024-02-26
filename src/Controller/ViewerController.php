<?php
namespace Drupal\collabora_online\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\collabora_online\Cool\CoolRequest;

/**
 * Provides route responses for the Collabora module.
 */
class ViewerController extends ControllerBase {

    /**
     * Returns a simple page.
     *
     * @return array
     *   A simple renderable array.
     */
    public function view() {
        $default_config = \Drupal::config('collabora_online.settings');
        $server = $default_config->get('server');

        $req = new CoolRequest();
        $req->getWopiSource();

        return [
            '#markup' => '<p>Hello from Collabora</p>' .
                '<p>We\'ll be loading from ' . $req->wopiSrc . '</p>' .
                '<iframe id="collabora-online-viewer" name="collabora-online-viewer" style="width:95%;height:80%;position:absolute;" src="' . $req->wopiSrc . '"></iframe>',

        ];
    }
}

?>
