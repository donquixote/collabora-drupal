<?php

declare(strict_types=1);

namespace Drupal\Tests\collabora_online\ExistingSiteJavascript;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use weitzman\DrupalTestTraits\ExistingSiteSelenium2DriverTestBase;

/**
 * Test for Collabora Online editors embedded in or accessed from Drupal.
 *
 * To make this a regular FunctionalJavascript test, the WOPI request from
 * Collabora to Drupal would need to send the SIMPLETEST_USER_AGENT cookie that
 * tells Drupal that the request should be handled by the test installation,
 * rather than the regular/existing installation.
 *
 * @see \drupal_valid_test_ua()
 */
class CollaboraIntegrationTest extends ExistingSiteSelenium2DriverTestBase {

    /**
     * Tests the Collabora editor in readonly mode.
     */
    public function testCollaboraPreview(): void {
        $user = $this->createUser([
            'preview document in collabora',
        ]);
        $this->drupalLogin($user);
        $media = $this->createDocumentMedia('Shopping list', 'shopping-list', 'Chocolate, pickles');
        $this->drupalGet('/cool/view/' . $media->id());
        $assert_session = $this->assertSession();
        $this->getSession()->switchToIFrame('collabora-online-viewer');
        $assert_session->waitForElement('css', 'canvas#document-canvas', 2000);
        $assert_session->elementExists('css', 'canvas#document-canvas');
        // Wait another second to let all UI elements load.
        sleep(1);
        // Make sure the correct document was opened.
        $this->assertSame(
            'shopping-list.txt',
            $assert_session->elementExists('css', 'input#document-name-input')->getValue(),
        );
        // The document text is in a canvas element, so instead we compare the
        // word count and character count.
        $this->assertSame(
            '2 words, 18 characters',
            $assert_session->elementExists('css', 'div#StateWordCount')->getText(),
        );
    }

    /**
     * Creates a media entity with an attached *.txt file.
     *
     * The *.txt format is enough to test the basic functionality.
     *
     * @param string $media_name
     *   Media label.
     * @param string $file_basename
     *   File name without the extension.
     * @param string $text_content
     *   Content for the attached *.txt file.
     *
     * @return \Drupal\media\MediaInterface
     *   New media entity.
     */
    protected function createDocumentMedia(string $media_name, string $file_basename, string $text_content): MediaInterface {
        $file_uri = 'public://' . $file_basename . '.txt';
        file_put_contents($file_uri, $text_content);
        $file = File::create([
            'uri' => $file_uri,
        ]);
        $file->save();
        $this->markEntityForCleanup($file);
        $values = [
            'bundle' => 'document',
            'name' => $media_name,
            'title' => $media_name,
            'label' => $media_name,
            'field_media_file' => $file->id(),
        ];
        $media = Media::create($values);
        $media->save();
        $this->markEntityForCleanup($media);
        return $media;
    }

}
