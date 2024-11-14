<?php

declare(strict_types=1);

namespace Drupal\Tests\collabora_online_group\Kernel;

use Drupal\Tests\group\Kernel\GroupKernelTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests group permissions.
 */
class PermissionTest extends GroupKernelTestBase {

    use MediaTypeCreationTrait;

    /**
     * {@inheritdoc}
     */
    protected static $modules = [
        'file',
        'media',
        'group',
        'groupmedia',
        'collabora_online_group',
    ];

    /**
     * Tests that group permissions are properly created.
     */
    public function testGroupPermissions(): void {
        // Generate types: group and media.
        $group_type_1 = $this->createGroupType();
        $group_type_2 = $this->createGroupType();
        $media_type_1 = $this->createMediaType('file');
        $media_type_2 = $this->createMediaType('file');

        // Enable relation plugins in groups.
        $this->entityTypeManager()->getStorage('group_relationship_type')
            ->createFromPlugin($group_type_1, 'group_media:' . $media_type_1->id(), [
                'group_cardinality' => 0,
                'entity_cardinality' => 1,
                'use_creation_wizard' => FALSE,
            ])
            ->save();
        $this->entityTypeManager()->getStorage('group_relationship_type')
            ->createFromPlugin($group_type_2, 'group_media:' . $media_type_1->id(), [
                'group_cardinality' => 0,
                'entity_cardinality' => 1,
                'use_creation_wizard' => FALSE,
            ])
            ->save();
        $this->entityTypeManager()->getStorage('group_relationship_type')
            ->createFromPlugin($group_type_2, 'group_media:' . $media_type_2->id(), [
                'group_cardinality' => 0,
                'entity_cardinality' => 1,
                'use_creation_wizard' => FALSE,
            ])
            ->save();

        // Check that permissions are generated for the groups.
        // The 'group_1' has only 'media_type_1' permissions.
        $permissions_group_1 = \Drupal::service('group.permissions')->getPermissionsByGroupType($group_type_1);
        $this->assertArrayHasKey("preview group_media:{$media_type_1->id()} in collabora", $permissions_group_1);
        $this->assertArrayHasKey("edit any group_media:{$media_type_1->id()} in collabora", $permissions_group_1);
        $this->assertArrayHasKey("edit own group_media:{$media_type_1->id()} in collabora", $permissions_group_1);
        $this->assertArrayNotHasKey("preview group_media:{$media_type_2->id()} in collabora", $permissions_group_1);
        $this->assertArrayNotHasKey("edit any group_media:{$media_type_2->id()} in collabora", $permissions_group_1);
        $this->assertArrayNotHasKey("edit own group_media:{$media_type_2->id()} in collabora", $permissions_group_1);
        // The 'group_2' has 'media_type_1' and 'media_type_2' permissions.
        $permissions_group_2 = \Drupal::service('group.permissions')->getPermissionsByGroupType($group_type_2);
        $this->assertArrayHasKey("preview group_media:{$media_type_1->id()} in collabora", $permissions_group_1);
        $this->assertArrayHasKey("edit any group_media:{$media_type_1->id()} in collabora", $permissions_group_1);
        $this->assertArrayHasKey("edit own group_media:{$media_type_1->id()} in collabora", $permissions_group_1);
        $this->assertArrayHasKey("preview group_media:{$media_type_2->id()} in collabora", $permissions_group_2);
        $this->assertArrayHasKey("edit any group_media:{$media_type_2->id()} in collabora", $permissions_group_2);
        $this->assertArrayHasKey("edit own group_media:{$media_type_2->id()} in collabora", $permissions_group_2);
    }

}
