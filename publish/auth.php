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
                'memory' => [ // 内置用户提供器
                    'users' => [
                        'admin' => [
                            'password' => 'admin',
                            'enabled' => true,
                        ],
                    ],
                ],
                // 'model' => [
                //     'class' => User::class,
                //     'identifier' => 'username',
                // ],
                // 'custom' => [ // 自定义用户提供器
                //     'class' => CustomUserProvider::class,
                //     'params' => []
                // ]
            ],
            'authenticators' => [
                'form_login' => [
                    'check_path' => '/admin/check_login',
                    // 'target_path' => '/',
                    // 'failure_path' => '/login',
                    // 'use_redirect_path' => false,
                    // 'redirect_path_param' => 'redirect_to',
                    // 'username_param' => 'username',
                    // 'password_param' => 'password',
                    // 'csrf_enabled' => true,
                    // 'csrf_param' => '_csrf_token',
                    // 'success_handler' => [
                    //     'class' => CustomSuccessHandler::class,
                    //     'params' => []
                    // ],
                    // 'failure_handler' => CustomFailureHandler::class,
                ],
                // 'json_login' => [
                //     'check_path' => '/admin/check_login',
                //     'success_handler' => [
                //         'class' => CustomSuccessHandler::class,
                //         'params' => []
                //     ],
                //     'failure_handler' => CustomFailureHandler::class,
                // ],
                // 'api_key' => [
                //     'check_path' => '/admin/check_login',
                //     'api_key_param' => 'X-API-Key',
                // ],
                // 'opaque_token' => [
                //     'header_param' => 'Authorization',
                //     'token_type' => 'Bearer',
                //     'issuer' => [
                //         'class' => CustomOpaqueTokenIssuer::class,
                //         'params' => []
                //     ],
                // ],
                // 'jwt' => [
                //     'algo'  => 'RS256',
                //     'private_key' => '',
                //     'public_key' => '',
                //     'pass_phrase' => '',
                //     'expire_in' => 3600,
                //     'blacklist_enabled' => true,
                //     'header_param' => 'Authorization',
                //     'token_type' => 'Bearer',
                // ],
                // 'custom' => [ // 自定义认证器
                //     [
                //         'class' => CustomAuthenticator::class,
                //         'params' => [],
                //     ]
                // ]
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
            // 'unauthenticated_handler' => [
            //     'class' => CustomUnauthenticatedHandler::class,
            //     'params' => []
            // ],
            // 'authorization_checker' => [
            //     'class' => CustomAuthorizationChecker::class,
            //     'params' => []
            // ],
            // 'access_denied_handler' => [
            //     'class' => CustomAccessDeniedHandler::class,
            //     'params' => []
            // ],
            'listeners' =>  [
                // CustomListener::class,
                // [
                //     'class' => CustomListener::class,
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
