<?php
namespace Drupal\collabora_online\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\collabora_online\Cool\CoolUtils;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides WOPI route responses for the Collabora module.
 */
class WopiController extends ControllerBase {

    private $fs;

    /**
     * The controller constructor.
     */
    public function __construct(FileSystemInterface $fs) {
        $this->fs = $fs;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): self {
        return new self(
            $container->get('file_system'),
        );
    }

    static function permissionDenied() {
        return new Response(
            "Anthentication failed.",
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

    /**
     * The WOPI entry point.
     *
     * action: 'info', 'content' or 'save'.
     * id: the ID of the media.
     * request: The request as originating.
     *
     * @return Response
     */
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
}

?>
