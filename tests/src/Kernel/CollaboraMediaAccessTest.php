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
            'media'
        ]);

        $this->createMediaType('file', ['id' => 'document']);

        // Consume the user id 1.
        $this->createUser();
    }

    /**
     * Verifies basic assumptions for this test.
     */
    public function testBasicAssumptions(): void {
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
     * Tests access for users with admin-like permissions.
     */
    public function testAdminAccess(): void {
        /** @var \Drupal\Core\Session\AccountInterface[] $accounts */
        $accounts = [
            'admin' => $this->createUser(admin: TRUE),
            'media admin' => $this->createUser(['administer media']),
        ];

        /** @var \Drupal\media\MediaInterface[] $media_entities */
        $media_entities = [
            'published document' => $this->createMediaEntity('document'),
            'unpublished document' => $this->createMediaEntity('document', [
                'status' => 0,
            ]),
        ];

        // The admin accounts should have access to all operations.
        $this->assertEntityAccess(
            [],
            $accounts,
            $media_entities,
            [
                'view',
                'preview in collabora',
                'edit in collabora',
            ],
            negate: TRUE,
        );

        // The admin accounts should have access to all paths.
        $this->assertEntityPathsAccess(
            [],
            $accounts,
            $media_entities,
            [
                '/media/%s',
                '/cool/view/%s',
                '/cool/edit/%s',
            ],
            negate: TRUE,
        );
    }

    /**
     * Tests permission combinations that don't grant any access.
     */
    public function testNoCollaboraMediaAccess(): void {
        /** @var \Drupal\Core\Session\AccountInterface[] $accounts */
        $accounts = [
            // The default roles for anonymous and authenticated are not created
            // in a kernel test. Therefore these users don't get any kind of
            // access.
            'anonymous' => new AnonymousUserSession(),
            'authenticated' => $this->createUser(),
            // Media permission alone are not sufficient for Collabora media
            // operations.
            'media only' => $this->createUser([
                'administer media types',
                'view media',
                'update any media',
                'view own unpublished media',
            ]),
            'collabora only' => $this->createUser([
                'preview document in collabora',
                'edit any document in collabora',
                'edit own document in collabora',
            ]),
            'not the author - media' => $this->createUser([
                // Require ownership for regular media view.
                'view own unpublished media',
                // Grant all Collabora permissions, even redundant ones.
                'preview document in collabora',
                'edit any document in collabora',
                'edit own document in collabora',
            ]),
            'not the author - collabora' => $this->createUser([
                // Grant all relevant media permissions.
                'view media',
                'view own unpublished media',
                'edit own document in collabora',
                // Require ownership to edit in collabora.
                'edit own document in collabora',
            ]),
        ];

        /** @var \Drupal\media\MediaInterface[] $media_entities */
        $media_entities = [
            'published document' => $this->createMediaEntity('document'),
            'unpublished document' => $this->createMediaEntity('document', [
                'status' => 0,
            ]),
        ];

        // The users created above are denied access for all the operations.
        $this->assertEntityAccess(
            [],
            $accounts,
            $media_entities,
            [
                'preview in collabora',
                'edit in collabora',
            ],
        );

        // The users created above get all paths denied.
        $this->assertEntityPathsAccess(
            [],
            $accounts,
            $media_entities,
            [
                '/cool/view/%s',
                '/cool/edit/%s',
            ],
        );
    }

    /**
     * Tests permission combinations that grant _some_ access.
     */
    public function testAccess(): void {
        /** @var \Drupal\Core\Session\AccountInterface[] $accounts */
        $accounts = [
            'readonly' => $this->createUser([
                'view media',
                'preview document in collabora',
            ]),
            'editor' => $this->createUser([
                'view media',
                'preview document in collabora',
                'edit any document in collabora',
            ]),
            'writeonly' => $this->createUser([
                'view media',
                'edit any document in collabora',
            ]),
            'owner' => $this->createUser([
                'view own unpublished media',
                'preview document in collabora',
                'edit own document in collabora',
            ]),
        ];

        /** @var \Drupal\media\MediaInterface[] $media_entities */
        $media_entities = [
            'published document' => $this->createMediaEntity('document', [
                'uid' => $accounts['owner']->id(),
            ]),
            'unpublished document' => $this->createMediaEntity('document', [
                'status' => 0,
                'uid' => $accounts['owner']->id(),
            ]),
        ];

        $this->assertEntityAccess(
            [
                'published document' => [
                    'preview in collabora' => ['readonly', 'editor'],
                    'edit in collabora' => ['editor', 'writeonly'],
                ],
                'unpublished document' => [
                    'preview in collabora' => ['owner'],
                    'edit in collabora' => ['owner'],
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
                '/cool/view/<published document>' => ['readonly', 'editor'],
                '/cool/edit/<published document>' => ['editor', 'writeonly'],
                '/cool/view/<unpublished document>' => ['owner'],
                '/cool/edit/<unpublished document>' => ['owner'],
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
     * @param bool $negate
     *   FALSE, if $expected describes access being granted.
     *   TRUE, if $expecte describes access being denied.
     */
    protected function assertEntityAccess(array $expected, array $accounts, array $entities, array $operations, bool $negate = FALSE): void {
        $actual = [];
        foreach ($entities as $media_key => $media) {
            foreach ($operations as $operation) {
                foreach ($accounts as $account_key => $account) {
                    $has_access = $media->access($operation, $account);
                    if ($has_access xor $negate) {
                        $actual[$media_key][$operation][] = $account_key;
                    }
                }
            }
        }
        // Use yaml to avoid integer keys in list output.
        $this->assertSame(
            "\n" . Yaml::encode($expected),
            "\n" . Yaml::encode($actual),
            $negate
                ? 'Users without access to given entities'
                : 'Users with access to given entities',
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
     * @param bool $negate
     *   FALSE, if $expected describes access being granted.
     *   TRUE, if $expecte describes access being denied.
     */
    protected function assertEntityPathsAccess(array $expected, array $accounts, array $entities, array $sprintf_path_patterns, bool $negate = FALSE) {
        $paths = [];
        // Build Collabora media paths for all media entities.
        foreach ($entities as $entity_key => $entity) {
            foreach ($sprintf_path_patterns as $pattern) {
                $paths[sprintf($pattern, "<$entity_key>")] = sprintf($pattern, $entity->id());
            }
        }
        $this->assertPathsAccessByUsers($expected, $accounts, $paths, $negate);
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
     * @param bool $negate
     *   FALSE, if $expected describes access being granted.
     *   TRUE, if $expecte describes access being denied.
     */
    protected function assertPathsAccessByUsers(array $expected, array $accounts, array $paths = NULL, bool $negate = FALSE): void {
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
                if ($has_access xor $negate) {
                    $actual[$path_key][] = $account_key;
                }
            }
        }
        // Use yaml to avoid integer keys in list output.
        $this->assertSame(
            "\n" . Yaml::encode($expected),
            "\n" . Yaml::encode($actual),
            $negate
                ? 'Users without access to given paths'
                : 'Users with access to given paths',
        );
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
