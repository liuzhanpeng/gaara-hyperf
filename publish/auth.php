<?php

return [
    'guards' => [
        'admin' => [
            'matcher' => [
                'pattern' => [ // 正则匹配
                    'expr' => '^/admin/',
                    // 'exclusion' => [
                    //     'admin/a',
                    //     'admin/b'
                    // ]
                ],
                // 'prefix' => [ // 前缀匹配
                //     'expr' => '/admin/',
                // ],
                // 'custom' => [ // 自定义匹配
                //     'class' => CustomRequestMatcher::class,
                //     'params' => []
                // ]
            ],
            'user_provider' => [
                //     'memory' => [ // 内置用户提供器
                //         'users' => [
                //             'admin' => [
                //                 'password' => 'admin',
                //                 'enabled' => true,
                //             ],
                //         ],
                //     ],
                // 'model' => [
                //     'class' => User::class,
                //     'identifier' => 'username',
                // ],
                'custom' => [ // 自定义用户提供器
                    'class' => CustomUserProvider::class,
                    'params' => []
                ]
            ],
            'authenticators' => [
                'form_login' => [
                    'login_path' => '/admin/login',
                    'check_path' => '/admin/check_login',
                    'redirect' => true,
                ],
                'json_login' => [
                    'check_path' => '/admin/check_login',
                    'success_handler' => CustomSuccessHandler::class,
                ],
                'custom' => [ // 自定义认证器
                    [
                        'class' => CustomAuthenticator::class,
                        'params' => [],
                    ]
                ]
            ],
            'logout' => [
                'path' => '/admin/logout',
                // 'target' => '/admin/login',
            ],
            'token_storage' => [
                'session' => [
                    'prefix' => 'auth.token',
                ],
                // 'custom' => [ // 自定义存储器
                //     'class' => CustomTokenStorage::class,
                //     'params' => []
                // ]
            ],
            // 'token_storage' => null,
            'unauthenticated_handler' => CustomUnauthenticatedHandler::class,
            // 'unauthenticated_handler' => [
            //     'class' => CustomUnauthenticatedHandler::class,
            //     'params' => []
            'authorization_checker' => CustomAuthorizationChecker::class,
            // 'authorization_checker' => [
            //     'class' => CustomAuthorizationChecker::class,
            //     'params' => []
            // ],
            'access_denied_handler' => CustomAccessDeniedHandler::class,
            // 'access_denied_handler' => [
            //     'class' => CustomAccessDeniedHandler::class,
            //     'params' => []
            // ],
            'listeners' =>  [
                UserExpireCheckListener::class,
                // [
                //     'class' => UserExpireCheckListener::class,
                //     'params' => []
                // ]
            ],
            // 'password_hasher' => [
            //     'default' => [
            //         'algo' => PASSWORD_BCRYPT,
            //     ],
            //     'custom' => [
            //         'class' => PasswordHasher::class,
            //         'params' => []
            //     ]
            // ]
        ],
    ],
];
