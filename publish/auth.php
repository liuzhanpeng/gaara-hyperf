<?php

return [
    'guards' => [
        'admin' => [
            'matcher' => [
                // 'type' => 'default', // 可选，暂时只支持default和custom，默认default
                'pattern' => '^/admin/', // type == default时必填，请求路径正则表达式
                // 'logout_path' => '/admin/logout', // type == default时可选，登出路径
                // 'exclusions' => [], // type == default时可选，排除的请求路径列表
            ],

            'user_provider' => [
                'type' => 'model', // 支持 memory, model, custom
                // 'users' => [ // type == memory时必填，内存用户列表
                //     'admin' => [
                //         'password' => 'admin',
                //     ],
                // ],
                // 'class' => User::class, // type == model时必填，用户模型类名
                // 'identifier' => 'username', // type == model时必填，用户模型标识字段
            ],

            'authenticators' => [
                'form_login' => [ // 内置表单登录认证器
                    'check_path' => '/admin/login', // 登录表单提交路径
                    // 'target_path' => '/admin/dashboard', // 登录成功跳转路径
                    // 'failure_path' => '/admin/login', // 登录失败跳转路径
                    // 'redirect_enabled' => true, // 是否启用登录成功后的重定向
                    // 'redirect_param' => 'redirect_to', // 重定向目标路径参数名
                    // 'username_param' => 'username', // 用户名参数名
                    // 'password_param' => 'password', // 密码参数名
                    // 'error_message' => '用户名或密码错误', // 登录失败错误消息; 支持字符串或回调函数; 回调函数参数为 AuthenticationException 实例
                    // 'csrf_enabled' => true, // 是否启用CSRF保护
                    // 'csrf_id' => 'authenticate', // CSRF令牌ID
                    // 'csrf_param' => '_csrf_token', // CSRF令牌参数名
                    // 'csrf_token_manager' => 'default', // CSRF令牌管理器服务名称
                    // 'success_handler' => [ // 可选，登录成功处理器配置; 没有参数时可以直接配置类名字符串
                    //     'class' => CustomSuccessHandler::class,
                    //     'args' => []
                    // ],
                    // 'success_handler' => CustomSuccessHandler::class, // 没有参数时可以直接配置类名字符串
                    // 'failure_handler' => [ // 可选，登录失败处理器配置
                    //     'class' => CustomFailureHandler::class,
                    //     'args' => []
                    // ],
                ],
                'json_login' => [ // 内置JSON登录认证器
                    'check_path' => '/admin/check_login', // JSON登录请求路径
                    // 'username_param' => 'username', // 用户名字段名
                    // 'password_param' => 'password', // 密码字段名
                    // 'success_handler' => [ // 可选，登录成功处理器配置; 无状态认证时一般都需要配置, 用于生成access token返回给客户端
                    //     'class' => OpaqueTokenResponseHandler::class,
                    //     'args' => [
                    //         'token_manager' => 'default',
                    //         'response_template' => '{ "code": 0, "msg": "success", "data": { "access_token": "#ACCESS_TOKEN#"} }',
                    //     ],
                    // ],
                    // 'failure_handler' => CustomFailureHandler::class // 可选，登录失败处理器配置
                ],
                'opaque_token' => [ // 不透明令牌认证器，用于API无状态认证； 一般配合 JSON登录认证器 使用
                    // 'token_manager' => 'default', // 可选；不透明令牌管理器服务名称; 默认default
                    // 'token_extractor' => 'admin_opaque_token_extractor' // 可选; 访问令牌提取器服务名称; 默认default
                    // 'success_handler' => CustomSuccessHandler::class // 可选，认证成功处理器配置
                    // 'failure_handler' => CustomFailureHandler::class // 可选，认证失败处理器配置
                ],
                'api_key' => [ // API-Key认证器
                    // 'api_key_param' => 'X-API-KEY', // 请求头中的api key参数名; 默认X-API-KEY; 
                ],
                'hmac_signature' => [ // HMAC签名认证器
                    // 'api_key_param' => 'X-API-KEY', // 请求头中的api key参数名; 默认X-API-KEY; 
                    // 'signature_param' => 'X-SIGNATURE', // 请求头中的签名参数名
                    // 'timestamp_param' => 'X-TIMESTAMP', // 请求头中的时间戳参数名
                    // 'nonce_enabled' => true, // 是否启用随机字符串; 防止重放攻击
                    // 'nonce_param' => 'X-NONCE', // nonce_enabled==true必须; 请求头中的随机字符串参数名 
                    // 'nonce_cache_prefix' => 'default', // nonce_enabled==true必须; 随机字符串缓存前缀
                    // 'ttl' => 60, // 请求签名的有效期，单位秒
                    // 'algo' => 'sha256', // 签名算法
                    // 'secret_crypto_enabled' => false, // 是否启用密钥加密
                    // 'secret_crypto_algo' => 'AES-256-CBC', // secret_crypto_enabled==true必须; 加密算法
                    // 'secret_crypto_key' => 'secret-key', // secret_crypto_enabled==true必须; 加密密钥
                ],
                'x509' => [ // X509证书认证器
                    // 'ssl_client_s_dn_param' => 'SSL_CLIENT_S_DN', // 存放客户端证书主题信息的服务器参数名
                    // 'identifier_field' => 'cn', // 用户标识field; 支持: cn, email; 根据实际情况设置
                ],
                // 'custom' => [ // 可设置多个自定义认证器；只要实现相关接口就可以了
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
                // [
                //     'class' => IPWhiteListListener::class,
                //     'args' => [
                //         'white_list' => [
                //             '192.168.1.1',
                //             '192.168.2.*',
                //             '172.31.0.0/16',
                //         ]
                //     ]
                // ],
                [
                    'class' => EnforcePasswordChangeListener::class,
                    'args' => [
                        'password_change_route' => 'admin/password'
                    ]
                ]

            ],
        ],
    ],

    'services' => [ // 全局服务配置
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
        // 'opaque_token_managers' => [ // 不透明令牌管理器配置; 内置了一个名称为default的管理器(type==default)
        //     'default' => [ // 不透明令牌管理器名称; 可按实际情况为每个Guard配置不同的管理器
        //         'type' => 'default', // 支持 default, custom; 默认default
        //         'prefix' => 'admin', // 存储前缀; 默认default; 多个管理器时必须配置不同的前缀
        //         'expires_in' => 60 * 20, // token过期时间，单位秒; 必须小于等于 max_lifetime; 默认1200秒
        //         'max_lifetime' => 60 * 60 * 24, // token最大生命周期，单位秒; 默认86400秒
        //         'token_refresh' => true, // 是否启用token刷新机制; 默认true
        //         'ip_bind_enabled' => false, // 是否启用IP绑定; 默认false
        //         'user_agent_bind_enabled' => false, // 是否启用User-Agent绑定; 默认false
        //         'single_session' => true, // 是否启用单会话登录; 默认true
        //         'access_token_length' => 16, // 生成令牌长度; 默认16
        //     ]
        // ],
        // 'access_token_extractors' => [ // 访问令牌提取器配置; 内置了一个名称为default的提取器(type==header)
        //     'default' => [ // 不透明令牌提取器名称; 可按实际情况为每个Guard配置不同的提取器 
        //         'type' => 'header', // 支持 header, cookie, custom; 默认header
        //         'param_name' => 'Authorization', // type == header时可选，默认Authorization; type == cookie时可选，默认access_token
        //         'param_type' => 'Bearer', // type == header时可选，默认Bearer
        //     ]
        // ],
    ],
];
