<?php

namespace Drupal\collabora_online\Cool;

use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

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
