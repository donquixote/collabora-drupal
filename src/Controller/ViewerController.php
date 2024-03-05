<?php
namespace Drupal\collabora_online\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\collabora_online\Cool\CoolRequest;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides route responses for the Collabora module.
 */
class ViewerController extends ControllerBase {

    private $renderer;

    /**
     * The controller constructor.
     */
    public function __construct(RendererInterface $renderer) {
        $this->renderer = $renderer;
    }
    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): self {
        return new self(
            $container->get('renderer'),
        );
    }

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
     * Returns a raw page for the iframe embed..
     *
     * @return array
     *   A simple renderable array.
     */
    public function editor() {
        $default_config = \Drupal::config('collabora_online.settings');
        $wopiBase = $default_config->get('collabora')['wopi_base'];

        $req = new CoolRequest();
        $wopiClient = $req->getWopiClientURL();

        $render_array = [
            'editor' => [
                '#wopiClient' => $wopiClient,
                '#wopiSrc' => urlencode($wopiBase . '/wopi/files/123'),
                '#accessToken' => 'test',
                '#theme' => 'collabora_online_full'
            ]
        ];
        $response = new Response();
        $response->setContent($this->renderer->renderRoot($render_array));

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
        $wopiBase = $default_config->get('collabora')['wopi_base'];

        $req = new CoolRequest();
        $wopiClient = $req->getWopiClientURL();

        $coolUrl = $wopiClient . 'WOPISrc=' . urlencode($wopiBase . '/wopi/files/123');

        return [
            '#theme' => 'collabora_online',
            '#wopiSrc' => $coolUrl,
            '#message1' => '<p>Hello from Collabora</p>' .
                '<p>We\'ll be loading from ' . $wopiClient . '</p>' .
                '<p>Error: ' . $req->errorString() . '</p>' .
                '<p>wopi base is set to ' . $wopiBase . '</p>',

        ];
    }
}

?>
