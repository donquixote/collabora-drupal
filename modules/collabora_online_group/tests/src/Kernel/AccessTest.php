<?php

declare(strict_types=1);

namespace Drupal\Tests\collabora_online_group\Kernel;

use Drupal\file\Entity\File;
use Drupal\group\PermissionScopeInterface;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\Tests\group\Kernel\GroupKernelTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\RoleInterface;

/**
 * Tests group permissions.
 */
class AccessTest extends GroupKernelTestBase {

    use MediaTypeCreationTrait;
    use UserCreationTrait;

    /**
     * {@inheritdoc}
     */
    protected static $modules = [
        'file',
        'media',
        'image',
        'group',
        'groupmedia',
        'collabora_online_group',
        'user'
    ];

    /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installEntitySchema('group_role');
    $this->installSchema('file', ['file_usage']);
  }

    /**
     * Tests that group permissions are properly created.
     * @dataProvider dataProvider
     */
    public function testCollaboraAccess(array $permissions, array $group_permissions, string $operation, bool $result): void {
        // Create group type, media type and enable plugin.
        $group_type = $this->createGroupType();
        $this->createGroupRole([
            'group_type' => $group_type->id(),
            'scope' => PermissionScopeInterface::INSIDER_ID,
            'global_role' => RoleInterface::AUTHENTICATED_ID,
            'permissions' => $group_permissions
        ]);
        $this->createMediaType('file', ['id' => 'document']);
        $this->entityTypeManager()->getStorage('group_relationship_type')
            ->createFromPlugin($group_type, 'group_media:document', [
                'group_cardinality' => 0,
                'entity_cardinality' => 1,
                'use_creation_wizard' => FALSE,
            ])
            ->save();

        // Add group, media and relation between both..
        $group = $this->createGroup(['type' => $group_type->id()]);
        $media = $this->createMediaEntity('document');
        $group->addRelationship($media, 'group_media:document');

        // Create the user with the given permissions and as member of the group.
        $user = $this->createUser($permissions);
        $group->addMember($user);

        // Check access.
        $this->assertEquals($result, $media->access($operation, $user));
   }

    /**
     * Data provider for File defaults by type widget.
     *
     * @return \Generator
     *   The test data.
     */
    protected function dataProvider(): \Generator {
    // User with no permissions at all.
    yield [
        [],
        [],
        'preview in collabora',
        FALSE
    ];
    yield [
        [],
        [
            'preview group_media:document in collabora'
        ],
        'preview in collabora',
        TRUE
    ];
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
