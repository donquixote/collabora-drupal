<?php

declare(strict_types=1);

/**
 * Implements hook_views_data_alter().
 */
function collabora_online_views_data_alter(array &$data): void {
    $data['media']['collabora_preview'] = [
        'title' => t('Link to preview in Collabora'),
        'group' => t('Media'),
        'field' => [
            'id' => 'media_collabora_preview',
        ],
    ];
    $data['media']['collabora_edit'] = [
        'title' => t('Link to edit in Collabora'),
        'group' => t('Media'),
        'field' => [
            'id' => 'media_collabora_edit',
        ],
    ];
}
