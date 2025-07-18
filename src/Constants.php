<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

/**
 * 常量类
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
final class Constants
{
    public const REQUEST_AUTHORIZATION_ATTRIBUTE = 'auth.authorization_attribute';
    public const REQUEST_AUTHORIZATION_SUBJECT = 'auth.authorization_subject';

    public const GUARD_PREFIX = 'auth.guard';
    public const PASSWORD_HASHER_PREFIX = 'auth.password_hasher';
    public const CSRF_TOKEN_MANAGER_PREFIX = 'auth.csrf_token_manager';
    public const OPAQUE_TOKEN_ISSUER_PREFIX = 'auth.opaque_token_issuer';

    private function __construct() {}
}
