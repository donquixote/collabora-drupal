<?php

declare(strict_types=1);

namespace Drupal\Tests\collabora_online_group\Kernel;

use Drupal\group\PermissionScopeInterface;
use Drupal\Tests\collabora_online\Traits\MediaCreationTrait;
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
     *
     * @dataProvider dataProvider
     */
    public function testCollaboraAccess(bool $expected_result, array $permissions, array $group_permissions, string $operation, string $scope = ''): void {
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

        // Add group, media and relation between both.
        $group = $this->createGroup(['type' => $group_type->id()]);
        $media = $this->createMediaEntity('document');
        $group->addRelationship($media, 'group_media:document');

        // Create the user with the given permissions and as member of the group.
        $user = $this->createUser($permissions);
        $group->addMember($user);

        if ($scope === 'own') {
            $media->setOwnerId($user->id())->save();
        }

        // Check access.
        $this->assertEquals($expected_result, $media->access($operation, $user));
   }

    /**
     * Data provider.
     *
     * @return \Generator
     *   The test data.
     */
    protected function dataProvider(): \Generator {
        // Preview: user with no permissions at all.
        yield [FALSE, [], [], 'preview in collabora'];
        // Preview: user with global permissions doesn't have access.
        yield [FALSE, ['preview document in collabora'], [], 'preview in collabora'];
        // Preview: user with group permissions have access.
        yield [TRUE, [], ['preview group_media:document in collabora'], 'preview in collabora'];
        // Edit any: user with no permissions at all.
        yield [FALSE, [], [], 'edit in collabora'];
        // Edit any: user with global permissions doesn't have access.
        yield [FALSE, ['edit any document in collabora'], [], 'edit in collabora'];
        // Edit any: User with group permissions have access.
        yield [TRUE, [], ['edit any group_media:document in collabora'], 'edit in collabora'];
        // Edit own: user with no permissions at all.
        yield [FALSE, [], [], 'edit in collabora', 'own'];
        // Edit own: user with global permissions doesn't have access.
        yield [FALSE, ['edit own document in collabora'], [], 'edit in collabora', 'own' ];
        // Edit own: user with group permissions have access.
        yield [TRUE, [], ['edit own group_media:document in collabora'], 'edit in collabora', 'own'];
   }

}
