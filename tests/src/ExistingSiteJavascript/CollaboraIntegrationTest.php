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
        $this->getSession()->switchToIFrame('collabora-online-viewer');

        $this->waitUntilNoMessage(function (): string|null {
            $canvas = $this->getCurrentPage()->find('css', 'canvas#document-canvas');
            if (!$canvas) {
                return 'Canvas element not found.';
            }
            return NULL;
        });

        // Make sure the correct document was opened.
        // Check the document name at the top of the editor.
        // Wait until the document name element appears and has non-empty text.
        $this->waitUntilNoMessage(function(): string|null {
            // Get a fresh element reference in each iteration, to avoid a
            // StaleElementReference exception.
            $element = $this->getCurrentPage()->find('css', 'input#document-name-input');
            if (!$element) {
                return 'Document name element not found.';
            }
            $text = $element->getValue();
            if (!$text) {
                return 'Document name is empty: ' . var_export($text, TRUE);
            }
            $this->assertSame('shopping-list.txt', $text);
            return NULL;
        });

        // The document text is in a canvas element, so instead we compare the
        // word count and character count.
        // Wait until the word counter element appears and has a value.
        $this->waitUntilNoMessage(function (): string|null {
            $element = $this->getCurrentPage()->find('css', 'div#StateWordCount');
            if (!$element) {
                return 'Word count element not found.';
            }
            $count_string = $element->getText();
            if (!$count_string) {
                return 'Word count string is empty.';
            }
            $this->assertSame('2 words, 18 characters', $count_string);
            return NULL;
        });
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

    /**
     * Waits until a callback returns NULL.
     *
     * @param callable(): (string|null) $callback
     *   Callback that is called in each iteration.
     *   It should return NULL to wait no longer, or a string message to wait
     *   and start another iteration.
     * @param int|float $max_seconds
     *   Maximum seconds of wait time.
     */
    protected function waitUntilNoMessage(callable $callback, int|float $max_seconds = 10): void {
        $start = microtime(TRUE);
        $end = $start + $max_seconds;
        do {
            $message = $callback();
            if ($message === NULL) {
                return;
            }
            usleep(10000);
        } while (microtime(TRUE) < $end);

        $this->fail('Timeout: ' . $message);
    }

}
