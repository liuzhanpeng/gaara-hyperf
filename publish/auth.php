<?php

return [
    'guards' => [
        'admin' => [
            'matcher' => [
                'pattern' => '^/admin/',
                'logout_path' => '/admin/logout',
                'exclusions' => [],
                'cache_size' => 100, // 可选，启用路径匹配缓存，设置缓存大小，0表示不启用缓存
            ],
            'user_provider' => [
                'memory' => [
                    'users' => [
                        'admin' => [
                            'password' => 'admin',
                        ],
                    ],
                ],
            ],
            'authenticators' => [
                'form_login' => [
                    'check_path' => '/admin/login',
                    'failure_path' => '/admin/login',
                    'csrf_enabled' => true,
                ],
                'json_login' => [
                    'check_path' => '/admin/check_login',
                    'success_handler' => [
                        'class' => OpaqueTokenResponseHandler::class,
                        'args' => [
                            // 'tokenIssuer' => 'admin_opaque_token_issuer',
                            // 'responseTemplate' => '{ "code": 0, "msg": "success", "data": { "access_token": "#TOKEN#"} }',
                        ],
                    ],
                    'failure_handler' => CustomFailureHandler::class,
                ],
                'opaque_token' => [
                    'token_issuer' => 'admin_opaque_token_issuer',
                ],
                // 'custom' => [
                //     [
                //         'class' => CustomAuthenticator::class,
                //         'args' => []
                //     ]
                // ]
            ],
            'token_storage' => 'session', // null 或 session
            'unauthenticated_handler' => [
                'redirect' => [
                    'target_path' => '/login',
                    'redirect_enabled' => true,
                    'redirect_param' => 'redirect_to'
                ],
                // 'custom' => [
                //    'class' => CustomUnauthenticatedHandler::class,
                //    'args' => []
                // ]
            ],
            // 'authorization' => [
            //     'checker' => [
            //         'class' => AuthorizationChecker::class,
            //         'args' => []
            //     ],
            //     'access_denied_handler' => [
            //         'class' => AccessDeniedHandler::class,
            //         'args' => []
            //     ],
            // ],
            'password_hasher' => 'admin',
            'login_rate_limiter' => [
                'sliding_window' => [
                    'max_attempts' => 5,
                    'interval' => 300,
                ]
            ],
            'listeners' =>  [
                // CustomListener::class,
            ],
        ],
    ],

    'services' => [
        'password_hashers' => [
            'admin' => [
                'default' => [
                    'algo' => PASSWORD_BCRYPT,
                ],
                // 'custom' => [
                //     'class' => PasswordHasher::class,
                //     'args' => []
                // ]
            ]
        ],
        'csrf_token_managers' => [
            'default' => [
                'session' => [
                    'prefix' => 'auth.csrf_token'
                ],
            ],
            // 'custom' => [
            //     'class' => CustomCsrfTokenManager::class,
            //     'args' => []
            // ]
        ],
        'opaque_token_issuers' => [
            'admin_opaque_token_issuer' => [
                'cache' => [
                    'prefix' => 'auth:opaque_token',
                    'header_param' => 'Authorization',
                    'token_type' => 'Bearer',
                    'expires_in' => 60 * 20, // token过期时间，单位秒
                    'max_lifetime' => 60 * 60 * 24, // token最大生命周期，单位秒
                    'token_refresh' => true,
                    'ip_bind_enabled' => false,
                    'user_agent_bind_enabled' => false,
                ],
                // 'custom' => [
                //     'class' => CustomOpaqueTokenIssuer::class,
                //     'args' => []
                // ]
            ]
        ],
    ],
];
