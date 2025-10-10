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
    public const __PREFIX = '__auth';

    public const TOKEN_CONTEXT_PREFIX = sprintf('%s.token_context', self::__PREFIX);

    public const REQUEST_AUTHORIZATION_ATTRIBUTE = sprintf('%s.authorization_attribute', self::__PREFIX);
    public const REQUEST_AUTHORIZATION_SUBJECT = sprintf('%s.authorization_subject', self::__PREFIX);

    public const GUARD_PREFIX = sprintf('%s.guard', self::__PREFIX);
    public const PASSWORD_HASHER_PREFIX = sprintf('%s.password_hasher', self::__PREFIX);
    public const CSRF_TOKEN_MANAGER_PREFIX = sprintf('%s.csrf_token_manager', self::__PREFIX);
    public const OPAQUE_TOKEN_ISSUER_PREFIX = sprintf('%s.opaque_token_issuer', self::__PREFIX);

    private function __construct() {}
}
