<?php
/*
 * Copyright the Collabora Online contributors.
 *
 * SPDX-License-Identifier: MPL-2.0
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

declare(strict_types=1);

namespace Drupal\Tests\collabora_online\FunctionalJavascript;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\key\Entity\Key;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests dynamically created permissions.
 */
class CollaboraPreviewTest extends WebDriverTestBase {

    use MediaTypeCreationTrait;
    use TestFileCreationTrait;

    /**
     * {@inheritdoc}
     */
    protected static $modules = [
        'system',
        'user',
        'media',
        'collabora_online',
    ];

    /**
     * {@inheritdoc}
     */
    protected $defaultTheme = 'stark';

    public function setUp(): void {
        parent::setUp();

        Key::create([
            'id' => 'collabora',
            'label' => 'Collabora',
            'key_type' => 'jwt_hs',
            'key_type_settings' => [
                'algorithm' => 'HS256',
            ],
            'key_provider' => 'config',
            'key_provider_settings' => [
                'key_value' => 'wwHYDJCstKi7pBfwtiV4y5iEDtnGaS+ALk2OR7DO5EZxSYkcnak5b0v1ZvdlpFXKP+RGijZvh7r+geV4SHJ4kw==',
                'base64_encoded' => false,
            ],
            'key_input' => 'text_field',
            'key_input_settings' => [
                'base64_encoded' => false,
            ],
        ])->save();

        \Drupal::configFactory()
            ->getEditable('collabora_online.settings')
            ->set('cool', [
                'server' => 'http://collabora.test:9980/',
                'wopi_base' => 'http://web.test:8080',
                'key_id' => 'collabora',
                'access_token_ttl' => 86400,
                'disable_cert_check' => TRUE,
            ])
            ->save(TRUE);

        \Drupal::configFactory()
            ->getEditable('jwt.config')
            ->set('key_id', 'collabora')
            ->save(TRUE);
    }

    /**
     * Tests a scenario where specific permissions are given to users.
     */
    public function testCollaboraPreview(): void {
        $this->createMediaType('file', ['id' => 'document', 'label' => 'Document']);
        $user = $this->createUser([
            'preview document in collabora',
        ]);
        $this->drupalLogin($user);
        $media = $this->createMediaEntity('document');
        $this->drupalGet('/cool/view/' . $media->id());
        $page = $this->getSession()->getPage();
        $assert_session = $this->assertSession();
        $iframe = $assert_session->elementExists('css', 'iframe[name="collabora-online-viewer"]');
        $this->getSession()->switchToIFrame('collabora-online-viewer');
        $this->assertSame('?', $this->getSession()->getPage()->getOuterHtml());
        $iframe_url = parse_url($iframe->getAttribute('src'));
        $this->assertSame('?', $iframe_url);
        $example_iframe_url = 'http://collabora.test:_PORT_/browser/_ID_/cool.html?WOPISrc=_WOPI_SRC_&closebutton=true';
        $pattern = '@^'
            . strtr(preg_quote($example_iframe_url, '@'), [
                '_PORT_' => '\d+',
                '_ID_' => '\w+',
                '_WOPI_SRC' => '[^\$]+',
            ])
            . '@';
        $this->assertMatchesRegularExpression($pattern, $iframe_url['path']);
    }

    /**
     * Creates a media entity with attached file.
     *
     * @param string $type
     *   Media type.
     * @param array $values
     *   Values for the media entity.
     *
     * @return \Drupal\media\MediaInterface
     *   New media entity.
     */
    protected function createMediaEntity(string $type, array $values = []): MediaInterface {
        file_put_contents('public://test.txt', 'Hello test');
        $file = File::create([
            'uri' => 'public://test.txt',
        ]);
        $file->save();
        $values += [
            'bundle' => $type,
            'field_media_file' => $file->id(),
        ];
        $media = Media::create($values);
        $media->save();
        return $media;
    }

}
