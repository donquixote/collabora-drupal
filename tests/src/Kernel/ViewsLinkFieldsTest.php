<?php

declare(strict_types=1);

namespace Drupal\Tests\collabora_online\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\collabora_online\Traits\MediaCreationTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\views\Views;

/**
 * Tests link fields to preview and edit medias in views.
 */
class ViewsLinkFieldsTest extends KernelTestBase {

    use UserCreationTrait;
    use MediaCreationTrait;
    use MediaTypeCreationTrait;

    /**
     * Media owned by current user.
     *
     * @var \Drupal\media\MediaInterface;
     */
    protected $ownMedia = NULL;

    /**
     * {@inheritdoc}
     */
    protected static $modules = [
        'collabora_online',
        'collabora_online_test',
        'field',
        'file',
        'image',
        'media',
        'system',
        'user',
        'views',
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void {
        parent::setUp();

        $this->installEntitySchema('file');
        $this->installEntitySchema('media');
        $this->installEntitySchema('user');
        $this->installConfig(['user', 'views', 'collabora_online_test']);
        $this->installSchema('file', ['file_usage']);
        // Install user module to avoid user 1 permissions bypass.
        \Drupal::moduleHandler()->loadInclude('user', 'install');
        user_install();

        // Create two medias to check access with different scopes, 'any' and 'own'.
        $this->createMediaEntity('document');
        $this->ownMedia = $this->createMediaEntity('document');
        ;
    }

    /**
     * Tests link fields.
     */
    public function testLinks(): void {
        // User without permissions can't see links.
        $this->doTestLinks(
            [
                'preview' => [FALSE, FALSE],
                'edit' => [FALSE, FALSE],
            ],
            $this->createUser([])
        );
        // User with 'Preview' permission can see preview link.
        $this->doTestLinks(
            [
                'preview' => [TRUE, TRUE],
                'edit' => [FALSE, FALSE],
            ],
            $this->createUser([
                'preview document in collabora'
            ])
        );
        // User with 'Edit any' permission can see edit link.
        $this->doTestLinks(
            [
                'preview' => [FALSE, FALSE],
                'edit' => [TRUE, TRUE],
            ],
            $this->createUser([
                'edit any document in collabora'
            ])
        );
        // User with 'Edit own' permission can see edit link for entities they own.
        $this->doTestLinks(
            [
                'preview' => [FALSE, FALSE],
                'edit' => [FALSE, TRUE],
            ],
            $this->createUser([
                'edit own document in collabora'
            ])
        );
    }

    /**
     * Tests that links behave as expected.
     *
     * @param array $expected_results
     *   An associative array of expected results keyed by operation.
     * @param \Drupal\Core\Session\AccountInterface $account
     *   The user account to be used to run the test.
     */
    protected function doTestLinks(array $expected_results, AccountInterface $account): void {
        $this->setCurrentUser($account);
        // Set the current user as the owner to check 'edit own' access.
        $this->ownMedia->setOwnerId($account->id())->save();
        $view = Views::getView('test_collabora_links');
        $view->preview();

        $info = [
            'preview' => [
                'label' => 'View in Collabora Online',
                'field_id' => 'collabora_preview',
                'route' => 'collabora-online.view'
            ],
            'edit' => [
                'label' => 'Edit in Collabora Online',
                'field_id' => 'collabora_edit',
                'route' => 'collabora-online.edit'
            ],
        ];

        $i = 0;
        // Check each expected results for every media.
        foreach (Media::loadMultiple() as $media) {
            foreach ($expected_results as $operation => $expected_result) {
                $expected_link = '';
                // The operation array contains results for each of the entities.
                if ($expected_result[$i]) {
                    $path = Url::fromRoute($info[$operation]['route'], ['media' => $media->id()])->toString();
                    $expected_link = '<a href="' . $path . '">' . $info[$operation]['label'] . '</a>';
                }
                // We check the output, whether it is a link or is empty (access denied).
                $link = $view->style_plugin->getField($i, $info[$operation]['field_id']);
                $this->assertEquals($expected_link, (string) $link);
            }
            $i++;
        }
    }

}
