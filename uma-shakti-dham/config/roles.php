<?php
// roles.php

return [
    'roles' => [
        'admin' => [
            'permissions' => [
                'create_user',
                'edit_user',
                'delete_user',
                'view_user',
                'create_moderator',
                'edit_moderator',
                'delete_moderator',
                'view_moderator',
            ],
        ],
        'moderator' => [
            'permissions' => [
                'view_user',
                'edit_user',
            ],
        ],
        'member' => [
            'permissions' => [
                'view_own_profile',
                'edit_own_profile',
                'manage_family_details',
                'make_donation',
            ],
        ],
        'guest' => [
            'permissions' => [
                'view_donation_page',
                'register',
                'login',
            ],
        ],
    ],
];
