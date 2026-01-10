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
            // 'Mini App' => [
            //     'route' => 'miniapp.index',
            //     'active' => 'miniapp/*',
            //     'icon' => 'file',
            //     // 'permissions' => [\Laravolt\Platform\Enums\Permission::MANAGE_USER],
            // ],
        ],
    ],
];
