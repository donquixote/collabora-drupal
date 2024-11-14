<?php

declare(strict_types=1);

namespace Drupal\Tests\collabora_online_group\Kernel;

use Drupal\group\PermissionScopeInterface;
use Drupal\Tests\collabora_online\Traits\MediaCreationTrait;
use Drupal\Tests\collabora_online_group\Traits\GroupRelationTrait;
use Drupal\Tests\group\Kernel\GroupKernelTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\RoleInterface;

/**
 * Tests Collabora group access.
 */
class AccessTest extends GroupKernelTestBase {

    use MediaTypeCreationTrait;
    use UserCreationTrait;
    use MediaCreationTrait;
    use GroupRelationTrait;

    /**
     * {@inheritdoc}
     */
    protected static $modules = [
        'file',
        'media',
        'image',
        'group',
        'groupmedia',
        'collabora_online',
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
     * Tests that access to Collabora group permissions is handled.
     */
    public function testCollaboraAccess(): void {
        // Create group type, media type and enable plugin.
        $group_type = $this->createGroupType();
        $group_role = $this->createGroupRole([
            'group_type' => $group_type->id(),
            'scope' => PermissionScopeInterface::INSIDER_ID,
            'global_role' => RoleInterface::AUTHENTICATED_ID,
            'permissions' => [],
        ]);
        $this->createMediaType('file', ['id' => 'document']);
        $this->createPluginRelation($group_type, 'group_media:document', [
            'group_cardinality' => 0,
            'entity_cardinality' => 1,
            'use_creation_wizard' => FALSE,
        ]);

        // Add group, media and relation between both.
        $group = $this->createGroup(['type' => $group_type->id()]);
        $media = $this->createMediaEntity('document');
        $group->addRelationship($media, 'group_media:document');

        // Iterate over each scenario.
        foreach ($this->dataProvider() as $scenario) {
            // Set the current permissions for the existing role.
            $group_role->set('permissions', $scenario['group_permissions'])->save();
            // Create the user with the given permissions and as member of the group.
            $user = $this->createUser($scenario['permissions']);
            $group->addMember($user);
            // Set user as owner if the scope is 'own'.
            $owner = $scenario['scope'] === 'own' ? $user->id() : 0;
            $media->setOwnerId($owner)->save();

            // Check access.
            $this->assertEquals($scenario['result'], $media->access($scenario['operation'], $user));
        }
   }

    /**
     * Data provider.
     *
     * @return array
     *   The test data.
     */
    protected function dataProvider(): array {
        return [
            // Preview: user with no permissions at all.
            [
                'result' => FALSE,
                'permissions' => [],
                'group_permissions' => [],
                'operation' => 'preview in collabora',
                'scope' => 'any'
            ],
            // Preview: user with global permissions doesn't have access.
            [
                'result' => FALSE,
                'permissions' => ['preview document in collabora'],
                'group_permissions' => [],
                'operation' => 'preview in collabora',
                'scope' => 'any'
            ],
            // Preview: user with group permissions have access.
            [
                'result' => TRUE,
                'permissions' => [],
                'group_permissions' => ['preview group_media:document in collabora'],
                'operation' => 'preview in collabora',
                'scope' => 'any'
            ],
            // Edit any: user with no permissions at all.
            [
                'result' => FALSE,
                'permissions' => [],
                'group_permissions' => [],
                'operation' => 'edit in collabora',
                'scope' => 'any'
            ],
            // Edit any: user with global permissions doesn't have access.
            [
                'result' => FALSE,
                'permissions' => ['edit any document in collabora'],
                'group_permissions' => [],
                'operation' => 'edit in collabora',
                'scope' => 'any'
            ],
            // Edit any: User with group permissions have access.
            [
                'result' => TRUE,
                'permissions' => [],
                'group_permissions' => ['edit any group_media:document in collabora'],
                'operation' => 'edit in collabora',
                'scope' => 'any'
            ],
            // Edit own: user with no permissions at all.
            [
                'result' => FALSE,
                'permissions' => [],
                'group_permissions' => [],
                'operation' => 'edit in collabora',
                'scope' => 'own'
            ],
            // Edit own: user with global permissions doesn't have access.
            [
                'result' => FALSE,
                'permissions' => ['edit own document in collabora'],
                'group_permissions' => [],
                'operation' => 'edit in collabora',
                'scope' => 'own'
            ],
            // Edit own: user with group permissions have access.
            [
                'result' => TRUE,
                'permissions' => [],
                'group_permissions' => ['edit own group_media:document in collabora'],
                'operation' => 'edit in collabora',
                'scope' => 'own'
            ],
        ];
   }

}
