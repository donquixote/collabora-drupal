<?php

declare(strict_types=1);

/**
 * Implements hook_views_data_alter().
 */
function collabora_online_views_data_alter(array &$data): void {
    $data['media']['collabora_preview'] =  [
        'title' => t('Preview operation in collabora'),
        'group' => t('Media'),
        'field' =>  [
            'title' => t('Media preview in collabora'),
            'id' => 'media_collabora_preview',
        ],
    ];
    $data['media']['collabora_edit'] =  [
        'title' => t('Edit operation in collabora'),
        'group' => t('Media'),
        'field' =>  [
            'title' => t('Media edit in collabora'),
            'id' => 'media_collabora_edit',
        ],
    ];
}
