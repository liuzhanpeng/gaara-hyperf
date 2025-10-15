<?php

use Lzpeng\HyperfAuthGuard\EventListener\IPWhiteListListener;
use Lzpeng\HyperfAuthGuard\Authenticator\OpaqueTokenResponseHandler;

return [
    'guards' => [
        'admin' => [
            'matcher' => [
                'type' => 'default',
                'pattern' => '^/admin/',
                'logout_path' => '/admin/logout',
                'exclusions' => [],
            ],

            'user_provider' => [
                'type' => 'memory',
                'users' => [
                    'admin' => [
                        'password' => 'admin',
                    ],
                ],
            ],

            'authenticators' => [
                'form_login' => [
                    'check_path' => '/admin/login',
                    'failure_path' => '/admin/login',
                    'csrf_enabled' => true,
                    'csrf_token_manager' => 'admin',
                    'csrf_token_param' => '_csrf_token',
                    'csrf_token_id' => 'authenticate',
                ],
                'json_login' => [
                    'check_path' => '/admin/check_login',
                    'success_handler' => [
                        'class' => OpaqueTokenResponseHandler::class,
                        'args' => [
                            // 'tokenIssuer' => 'admin_opaque_token_issuer',
                            // 'responseTemplate' => '{ "code": 0, "msg": "success", "data": { "access_token": "#ACCESS_TOKEN#"} }',
                        ],
                    ],
                    'failure_handler' => 'CustomFailureHandler', // 字符串形式，用户需要实现此类
                ],
                'opaque_token' => [
                    'token_manager' => 'admin_opaque_token_manager',
                    'token_extractor' => 'admin_opaque_token_extractor'
                ],
                'api_signature' => [
                    'api_key_param' => 'X-API-KEY',
                    'signature_param' => 'X-SIGNATURE',
                    'timestamp_param' => 'X-TIMESTAMP',
                    'nonce_param' => 'X-NONCE',
                    'ttl' => 60, // 请求签名的有效期，单位秒
                    'algo' => 'HMAC-SHA256', // 签名算法
                    // 'secret_crypto_enabled' => true, // 是否启用密钥加密
                    // 'secret_crypto_algo' => 'AES-256-CBC', // 密钥加密算法
                    // 'secret_crypto_key' => 'xxx', // base64编码的密钥
                ],
                'x509' => [
                    'verify_mode' => 'strict', // strict, optional
                    'identifier_field' => 'cn', // cn, serial, fingerprint, subject_dn, email
                    'check_validity' => true, // 是否检查证书有效期
                    'allowed_cas' => [], // 允许的CA指纹列表，空表示不限制
                    'revocation_check' => false, // 是否检查证书撤销状态
                ],
                // 'custom' => [
                //     [
                //         'class' => CustomAuthenticator::class,
                //         'args' => []
                //     ]
                // ]
            ],

            'token_storage' => [
                'type' => 'null',

                // or   
                // 'type' => 'session',
                // 'prefix' => 'admin',
            ],

            'unauthenticated_handler' => [
                'type' => 'redirect',
                'target_path' => '/login',
                'redirect_enabled' => true,
                'redirect_param' => 'redirect_to'
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
                'type' => 'sliding_window',
                'limit' => 5,
                'interval' => 300,
            ],
            'listeners' =>  [
                // CustomListener::class,
                [
                    'class' => IPWhiteListListener::class,
                    'args' => [
                        'whiteList' => ['192.168.1.1']
                    ]
                ]
            ],
        ],
    ],

    'services' => [
        'password_hashers' => [
            'admin' => [
                'type' => 'default',
                'algo' => PASSWORD_BCRYPT,

                // or
                // 'type' => 'custom',
                // 'class' => CustomPasswordHasher::class,
                // 'args' => []
            ],
            // 'api' => [
            //     'type' => 'default',
            //     'algo' => PASSWORD_DEFAULT
            // ]
        ],
        'csrf_token_managers' => [
            'admin' => [
                'type' => 'session',
                'prefix' => 'admin'
            ]
        ],
        'opaque_token_managers' => [
            'admin_opaque_token_manager' => [
                'type' => 'default',
                'prefix' => 'admin',
                'expires_in' => 60 * 20, // token过期时间，单位秒
                'max_lifetime' => 60 * 60 * 24, // token最大生命周期，单位秒
                'token_refresh' => true,
                'ip_bind_enabled' => false,
                'user_agent_bind_enabled' => false,
                'single_session' => true,
            ]
        ],
        'access_token_extractors' => [
            'admin_opaque_token_extractor' => [
                'type' => 'header',
                'param_name' => 'Authorization',
                'param_type' => 'Bearer',

                // or
                // 'type' => 'cookie',
                // 'param_name' => 'access_token',
            ]
        ],
    ]
];
