<?php
namespace Drupal\collabora_online\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\collabora_online\Cool\CoolUtils;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides WOPI route responses for the Collabora module.
 */
class WopiController extends ControllerBase {

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
        $user = User::load($jwt_payload->uid);
        $avatarUrl = \Drupal::service('file_url_generator')->generateAbsoluteString($user->user_picture->entity->getFileUri());

        $payload = [
            'BaseFileName' => $file->getFilename(),
            'Size' => $file->getSize(),
            'UserId' => $jwt_payload->uid,
            'UserFriendlyName' => $user->getDisplayName(),
            'UserExtraInfo' => [
                'avatar' => $avatarUrl,
                'mail' => $user->getEmail(),
            ],
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

        $jwt_payload = CoolUtils::verifyTokenForId($token, $id);
        if ($jwt_payload == null) {
            return static::permissionDenied();
        }

        $fs = \Drupal::service('file_system');

        $media = \Drupal::entityTypeManager()->getStorage('media')->load($id);
        $user = User::load($jwt_payload->uid);

        $file = CoolUtils::getFile($media);
        $dir = $fs->dirname($file->getFileUri());
        $dest = $dir . '/' . $file->getFilename();

        $content = $request->getContent();
        $owner_id = $file->getOwnerId();
        $uri = $fs->saveData($content, $dest, FileSystemInterface::EXISTS_RENAME);

        $file = File::create(['uri' => $uri]);
        $file->setOwnerId($owner_id);
        if (is_file($dest)) {
            $file->setFilename($fs->basename($dest));
        }
        $file->setPermanent();
        $file->setSize(strlen($content));
        $file->save();

        CoolUtils::setMediaSource($media, $file);
        $media->setRevisionUser($user);
        $media->setRevisionCreationTime(\Drupal::service('datetime.time')->getRequestTime());
        // XXX we should have a proper reason.
        $media->setRevisionLogMessage('Saved by Collabora Online');
        $media->save();

        $response = new Response(
            'Put File implemented would saved to ' . $dest,
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
