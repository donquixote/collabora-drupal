<?php

namespace Drupal\collabora_online\Cool;

use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class CoolUtils {
    /** Get the file from the Media entity */
    public static function getFile(Media $media) {
        $fid = $media->getSource()->getSourceFieldValue($media);
        $file = File::load($fid);

        return $file;
    }

    /** Get the file based on the entity id */
    public static function getFileById($id) {
        $media = \Drupal::entityTypeManager()->getStorage('media')->load($id);
        return CoolUtils::getFile($media);
    }

    static function getKey() {
        $default_config = \Drupal::config('collabora_online.settings');
        $key_id = $default_config->get('collabora')['key_id'];

        $key = \Drupal::service('key.repository')->getKey($key_id)->getKeyValue();
        return $key;
    }

    /** Verify JWT ***/
    public static function verifyTokenForId($token, $id) {
        $key = static::getKey();
        $payload = JWT::decode($token, new Key($key, 'HS256'));

        if (($payload->fid == $id) && ($payload->exp >= gettimeofday(true))) {
            return $payload;
        }

        return null;
    }

    public static function tokenForFileId($id) {
        $payload = [
            "fid" => $id,
            "uid" => \Drupal::currentUser()->id(),
            "exp" => gettimeofday(true) + 3600 * 24,
        ];
        $key = static::getKey();
        $jwt = JWT::encode($payload, $key, 'HS256');

        return $jwt;
    }

    /** Return if we can edit that media file. */
    public static function canEdit(Media $media) {
        // XXX todo
        return TRUE;
    }

    /** Return the mime type for the document. */
    public static function getDocumentType(File $file) {
        return "foo";
    }

    public static function getEditorUrl(Media $media) {
        return Url::fromRoute('collabora-online.view', ['media' => $media->id()]);
    }
}
