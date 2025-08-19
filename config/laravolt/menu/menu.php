<?php

return [
    'Menu' => [
        'order' => 01,
        'menu' => [
            'Pages' => [
                'route' => 'page.index',
                'active' => 'page/*',
                'icon' => 'file',
                // 'permissions' => [\Laravolt\Platform\Enums\Permission::MANAGE_USER],
            ],
        ],
    ],
];
