<?php

declare(strict_types=1);

namespace Drupal\Tests\collabora_online\Kernel;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests access for media operations and routes.
 */
class CollaboraMediaAccessTest extends KernelTestBase {

    use MediaTypeCreationTrait;
    use UserCreationTrait;

    /**
     * {@inheritdoc}
     */
    protected static $modules = [
        'collabora_online',
        'user',
        'media',
        'field',
        'image',
        'system',
        'file',
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void {
        parent::setUp();

        $this->installEntitySchema('user');
        $this->installEntitySchema('file');
        $this->installSchema('file', 'file_usage');
        $this->installEntitySchema('media');
        $this->installConfig([
            'field',
            'system',
            'user',
            'image',
            'file',
            'media',
        ]);

        $this->createMediaType('file', ['id' => 'document']);
        $this->createMediaType('file', ['id' => 'book']);

        // Consume the user id 1.
        $this->createUser();
    }

    /**
     * Tests media access for Collabora routes and operations.
     */
    public function testCollaboraMediaAccess(): void {
        $this->assertBasicAssumptions();

        /** @var \Drupal\Core\Session\AccountInterface[] $accounts */
        $accounts = [
            // The default roles for anonymous and authenticated are not created
            // in a kernel test. Therefore these users don't get any kind of
            // access.
            'anonymous' => new AnonymousUserSession(),
            'authenticated' => $this->createUser(),
            // Media permission do not grant access to Collabora operations.
            'media permissions only' => $this->createUser([
                'administer media types',
                'view media',
                'update any media',
                'view own unpublished media',
            ]),
            // Permissions for 'book' do not grant access to 'document'.
            'Bookworm' => $this->createUser([
                'preview book in collabora',
                'edit any book in collabora',
                'edit own book in collabora',
            ]),
            'Previewer' => $this->createUser([
                'preview document in collabora',
            ]),
            'Editor' => $this->createUser([
                'edit any document in collabora',
            ]),
            'Kelly (edit own)' => $this->createUser([
                'edit own document in collabora',
            ]),
            'Media admin' => $this->createUser([
                'administer media',
            ]),
        ];

        /** @var \Drupal\media\MediaInterface[] $media_entities */
        $media_entities = [
            'published document' => $this->createMediaEntity('document'),
            'unpublished document' => $this->createMediaEntity('document', [
                'status' => 0,
            ]),
            "Kelly's published document" => $this->createMediaEntity('document', [
                'uid' => $accounts['Kelly (edit own)']->id(),
            ]),
            "Kelly's unpublished document" => $this->createMediaEntity('document', [
                'uid' => $accounts['Kelly (edit own)']->id(),
                'status' => 0,
            ]),
        ];

        $this->assertEntityAccess(
            [
                'published document' => [
                    'preview in collabora' => ['Previewer', 'Media admin'],
                    'edit in collabora' => ['Editor', 'Media admin'],
                ],
                'unpublished document' => [
                    'preview in collabora' => ['Previewer', 'Media admin'],
                    'edit in collabora' => ['Editor', 'Media admin'],
                ],
                "Kelly's published document" => [
                    'preview in collabora' => ['Previewer', 'Media admin'],
                    'edit in collabora' => ['Editor', 'Kelly (edit own)', 'Media admin'],
                ],
                "Kelly's unpublished document" => [
                    'preview in collabora' => ['Previewer', 'Media admin'],
                    'edit in collabora' => ['Editor', 'Kelly (edit own)', 'Media admin'],
                ],
            ],
            $accounts,
            $media_entities,
            [
                'preview in collabora',
                'edit in collabora',
            ],
        );

        $this->assertEntityPathsAccess(
            [
                "/cool/view/<published document>" => ['Previewer', 'Media admin'],
                "/cool/edit/<published document>" => ['Editor', 'Media admin'],
                "/cool/view/<unpublished document>" => ['Previewer', 'Media admin'],
                "/cool/edit/<unpublished document>" => ['Editor', 'Media admin'],
                "/cool/view/<Kelly's published document>" => ['Previewer', 'Media admin'],
                "/cool/edit/<Kelly's published document>" => ['Editor', 'Kelly (edit own)', 'Media admin'],
                "/cool/view/<Kelly's unpublished document>" => ['Previewer', 'Media admin'],
                "/cool/edit/<Kelly's unpublished document>" => ['Editor', 'Kelly (edit own)', 'Media admin'],
            ],
            $accounts,
            $media_entities,
            [
                '/cool/view/%s',
                '/cool/edit/%s',
            ],
        );
    }

    /**
     * Asserts which users can access which entity operations.
     *
     * @param array<string, array<string, list<string>>> $expected
     *   Expected outcome.
     *   The array is keyed by entity key and operation.
     *   The values are lists of keys from the $accounts parameter.
     * @param array<string, \Drupal\Core\Session\AccountInterface> $accounts
     *   Entities to check.
     * @param array<string, \Drupal\Core\Entity\EntityInterface> $entities
     *   Operations to check.
     * @param list<string> $operations
     *   User accounts to check.
     */
    protected function assertEntityAccess(array $expected, array $accounts, array $entities, array $operations): void {
        $actual = [];
        foreach ($entities as $media_key => $media) {
            foreach ($operations as $operation) {
                foreach ($accounts as $account_key => $account) {
                    $has_access = $media->access($operation, $account);
                    if ($has_access) {
                        $actual[$media_key][$operation][] = $account_key;
                    }
                }
            }
        }
        // Use yaml to avoid integer keys in list output.
        $this->assertSame(
            "\n" . Yaml::encode($expected),
            "\n" . Yaml::encode($actual),
            'Users with access to given entities',
        );
    }

    /**
     * Asserts which users have access to which entity paths.
     *
     * @param array<string, list<string>> $expected
     *   Array indicating which url should be accessible by which user.
     *   The array keys are either string keys from the $paths array.
     *   The array values are lists of keys from the $accounts array with access
     *   to that path.
     * @param array<string, \Drupal\Core\Session\AccountInterface> $accounts
     *   Accounts to test access with, keyed by a distinguishable name.
     * @param array<string, \Drupal\Core\Entity\EntityInterface> $entities
     *   Entities for which to build paths.
     * @param array<string, string> $sprintf_path_patterns
     *   Path patterns with '%s' placeholder for the entity id.
     */
    protected function assertEntityPathsAccess(array $expected, array $accounts, array $entities, array $sprintf_path_patterns) {
        $paths = [];
        // Build Collabora media paths for all media entities.
        foreach ($entities as $entity_key => $entity) {
            foreach ($sprintf_path_patterns as $pattern) {
                $paths[sprintf($pattern, "<$entity_key>")] = sprintf($pattern, $entity->id());
            }
        }
        $this->assertPathsAccessByUsers($expected, $accounts, $paths);
    }

    /**
     * Asserts which users have access to which paths.
     *
     * @param array<string, list<string>> $expected
     *   Array indicating which url should be accessible by which user.
     *   The array keys are either paths or string keys from the $paths array.
     *   The array values are lists of keys from the $accounts array with access
     *   to that path.
     * @param array<string, \Drupal\Core\Session\AccountInterface> $accounts
     *   Accounts to test access with, keyed by a distinguishable name.
     * @param array<string, string>|null $paths
     *   An array of paths, or NULL to just use the array keys from $expected.
     *   This parameter is useful if the paths all look very similar.
     */
    protected function assertPathsAccessByUsers(array $expected, array $accounts, ?array $paths = NULL): void {
        if ($paths === NULL) {
            $paths = array_keys($expected);
            $paths = array_combine($paths, $paths);
        }
        // Build a report and assert it all at once, to have a more complete
        // overview on failure.
        $actual = [];
        foreach ($paths as $path_key => $path) {
            $url = Url::fromUserInput($path);
            // Filter the user list by access to the url.
            foreach ($accounts as $account_key => $account) {
                $has_access = $url->access($account);
                if ($has_access) {
                    $actual[$path_key][] = $account_key;
                }
            }
        }
        // Use yaml to avoid integer keys in list output.
        $this->assertSame(
            "\n" . Yaml::encode($expected),
            "\n" . Yaml::encode($actual),
            'Users with access to given paths',
        );
    }

    /**
     * Verifies basic assumptions for this test.
     *
     * It is enough to call this from one test method.
     */
    protected function assertBasicAssumptions(): void {
        // Normally, when media module is installed, media_install() will grant
        // the 'view media' permission to anonymous and authenticated roles.
        // In a kernel test this does not happen.
        $this->assertSame([], Role::load(RoleInterface::ANONYMOUS_ID)->getPermissions());
        $this->assertSame([], Role::load(RoleInterface::AUTHENTICATED_ID)->getPermissions());

        // The first user created in a test method is not user 1.
        $user = $this->createUser();
        $this->assertSame(2, (int) $user->id());
        $this->assertFalse($user->hasPermission('view media'));
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
