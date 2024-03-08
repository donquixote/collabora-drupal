<?php
namespace Drupal\collabora_online\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\collabora_online\Cool\CoolRequest;
use Drupal\collabora_online\Cool\CoolUtils;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

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

    static function permissionDenied() {
        return new Response(
            'Authentication failed.',
            Response::HTTP_FORBIDDEN,
            ['content-type' => 'text/plain'],
        );
    }

    function wopiCheckFileInfo(string $id, Request $request) {
        $token = $request->query->get('access_token');

        $jwt_payload = CoolUtils::verifyTokenForId($token, $id);
        if ($jwt_payload == null) {
            return static::permissionDenied();
        }

        $file = CoolUtils::getFileById($id);

        // the Size property is the length of the string
        // returned in wopiGetFile
        $payload = [
            'BaseFileName' => $file->getFilename(),
            'Size' => $file->getSize(),
            'UserId' => $jwt_payload->uid,
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

    function wopiGetFile(string $id, Request $request) {
        $token = $request->query->get('access_token');

        if (!CoolUtils::verifyTokenForId($token, $id)) {
            return static::permissionDenied();
        }

        $file = CoolUtils::getFileById($id);
        $mimetype = $file->getMimeType();

        $response = new BinaryFileResponse(
            $file->getFileUri(),
            Response::HTTP_OK,
            ['content-type' => $mimetype]
        );
        return $response;
    }

    function wopiPutFile(string $id, Request $request) {
        $token = $request->query->get('access_token');

        if (!CoolUtils::verifyTokenForId($token, $id)) {
            return static::permissionDenied();
        }

        $file = CoolUtils::getFileById($id);

        $response = new Response(
            'Put File not implemented',
            Response::HTTP_OK,
            ['content-type' => 'text/plain']
        );
        return $response;
    }

    public function wopi(string $action, string $id, Request $request) {
        $returnCode = Response::HTTP_BAD_REQUEST;
        switch ($action) {
        case 'info':
            return $this->wopiCheckFileInfo($id, $request);
            break;
        case 'content':
            return $this->wopiGetFile($id, $request);
            break;
        case 'save':
            return $this->wopiPutFile($id, $request);
            break;
        }

        $response = new Response(
            'Invalid WOPI action ' . $action,
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
    public function editor(Media $media) {
        $default_config = \Drupal::config('collabora_online.settings');
        $wopiBase = $default_config->get('collabora')['wopi_base'];

        $req = new CoolRequest();
        $wopiClient = $req->getWopiClientURL();

        $id = $media->id();

        $accessToken = CoolUtils::tokenForFileId($id);

        $render_array = [
            'editor' => [
                '#wopiClient' => $wopiClient,
                '#wopiSrc' => urlencode($wopiBase . '/wopi/files/' . $id),
                '#accessToken' => $accessToken,
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

        $accessToken = CoolUtils::tokenForFileId($id);

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
