<?php

declare(strict_types=1);

namespace Drupal\Tests\collabora_online_group\Kernel;

use Drupal\Component\DependencyInjection\Container;
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
    ];

    /**
     * Tests that group permissions are properly created.
     */
    public function testGroupPermissions(): void {
        // Generate types: group and media.
        $group_type_1 = $this->createGroupType();
        $group_type_2 = $this->createGroupType();
        $group_type_3 = $this->createGroupType();
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
        // Save current permissions count for each group.
        $permissions_handler = \Drupal::service('group.permissions');
        $count_group_1 = count($permissions_handler->getPermissionsByGroupType($group_type_1));
        $count_group_2 = count($permissions_handler->getPermissionsByGroupType($group_type_2));
        $count_group_3 = count($permissions_handler->getPermissionsByGroupType($group_type_3));

        // Check collabora permissions in each group.
        $this->enableModules(['collabora_online_group']);
        // The 'group_1' has only 'media_type_1' permissions.
        drupal_static_reset();
        $permissions_group_1 = \Drupal::service('group.permissions')->getPermissionsByGroupType($group_type_1);
        $this->assertCollaboraPermissions('group_media:' . $media_type_1->id(), $permissions_group_1);
        $this->assertCollaboraPermissions('group_media:' . $media_type_2->id(), $permissions_group_1, FALSE);
        $this->assertCount($count_group_1 + 3, $permissions_group_1);
        // The 'group_2' has 'media_type_1' and 'media_type_2' permissions.
        $permissions_group_2 = $permissions_handler->getPermissionsByGroupType($group_type_2);
        $this->assertCollaboraPermissions('group_media:' . $media_type_1->id(), $permissions_group_2);
        $this->assertCollaboraPermissions('group_media:' . $media_type_2->id(), $permissions_group_2);
        $this->assertCount($count_group_2 + 6, $permissions_group_2);
        // The 'group_3' doesn't have any new permissions.
        $permissions_group_3 = $permissions_handler->getPermissionsByGroupType($group_type_3);
        $this->assertCollaboraPermissions('group_media:' . $media_type_1->id(), $permissions_group_3, FALSE);
        $this->assertCollaboraPermissions('group_media:' . $media_type_2->id(), $permissions_group_3, FALSE);
        $this->assertCount($count_group_3, $permissions_group_3);
    }

    /**
     * Asserts that collabora permissions are present or not in a given array.
     *
     * @param string $id
     *   The entity ID.
     * @param array $permissions
     *   The permission where to perform the checks.
     * @param bool $enabled
     *   If the permissions are enabled.
     */
    protected function assertCollaboraPermissions(string $id, array $permissions, bool $enabled = TRUE): void {
        $expected_permissions = [
            "preview $id in collabora" => "Entity: Preview media item in collabora",
            "edit any $id in collabora" => "Entity: Edit any media item in collabora",
            "edit own $id in collabora" => "Entity: Edit own media item in collabora",
        ];

        foreach($expected_permissions as $name => $description) {
            if ($enabled === FALSE) {
                $this->assertArrayNotHasKey($name, $permissions);
                continue;
            }
           $this->assertEquals($description, $permissions[$name]['title']);
        }
    }
}
