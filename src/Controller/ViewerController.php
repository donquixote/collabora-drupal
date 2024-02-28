<?php
namespace Drupal\collabora_online\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\collabora_online\Cool\CoolRequest;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides route responses for the Collabora module.
 */
class ViewerController extends ControllerBase {

    function wopiCheckFileInfo(string $id) {
        // test.txt is just a fake text file
        // the Size property is the length of the string
        // returned in wopiGetFile
        $payload = [
            'BaseFileName' => 'test.txt',
            'Size' => 11,
            'UserId' => 1,
            'UserCanWrite' => true
        ];

        $jsonPayload = json_encode($payload);

        $response = new Response(
            $jsonPayload,
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
        return $response;
    }

    function wopiGetFile() {
        $response = new Response(
            'Hello WOPI ' . $id . ' action ' . $action,
            Response::HTTP_OK,
            ['content-type' => 'text/plain']
        );
        return $response;
    }

    function wopiPutFile() {
        $response = new Response(
            '<p>WOPI ' . $id . ' action ' . $action . '</p>',
            Response::HTTP_OK,
            ['content-type' => 'text/plain']
        );
        return $response;
    }

    public function wopi(string $action, string $id) {
        $returnCode = Response::HTTP_BAD_REQUEST;
        switch ($action) {
        case 'info':
            return $this->wopiCheckFileInfo($id);
            break;
        case 'content':
            return $this->wopiGetFile($id);
            break;
        case 'save':
            $returnCode = Response::HTTP_OK;
            break;
        }

        $response = new Response(
            '<p>WOPI ' . $id . ' action ' . $action . '</p>',
            $returnCode,
            ['content-type' => 'text/plain']
        );
        return $response;
    }

    /**
     * Returns a simple page.
     *
     * @return array
     *   A simple renderable array.
     */
    public function view() {
        $default_config = \Drupal::config('collabora_online.settings');
        $server = $default_config->get('collabora')['server'];

        $req = new CoolRequest();
        $req->getWopiSource();

        return [
            '#markup' => '<p>Hello from Collabora</p>' .
                '<p>We\'ll be loading from ' . $req->wopiSrc . '</p>' .
                '<p>Error: ' . $req->errorString() . '</p>' .
                '<p>server is set to ' . $server . '</p>' .
                '<iframe id="collabora-online-viewer" name="collabora-online-viewer" style="width:95%;height:80%;position:absolute;" src="' . $req->wopiSrc . '"></iframe>',

        ];
    }
}

?>
