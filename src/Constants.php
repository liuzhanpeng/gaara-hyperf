<?php

declare(strict_types=1);

namespace GaaraHyperf;

/**
 * 常量类
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
final class Constants
{
    public const __PREFIX = 'gaara_auth';

    public const TOKEN_CONTEXT_PREFIX = self::__PREFIX . '.token_context';

    public const REQUEST_AUTHORIZATION_ATTRIBUTE = self::__PREFIX . '.authorization_attribute';
    public const REQUEST_AUTHORIZATION_SUBJECT = self::__PREFIX . '.authorization_subject';

    public const GUARD_PREFIX = self::__PREFIX . '.guard';
    public const PASSWORD_HASHER_PREFIX = self::__PREFIX . '.password_hasher';
    public const CSRF_TOKEN_MANAGER_PREFIX = self::__PREFIX . '.csrf_token_manager';
    public const OPAQUE_TOKEN_MANAGER_PREFIX = self::__PREFIX . '.opaque_token_manager';
    public const ACCESS_TOKEN_EXTRACTOR_PREFIX = self::__PREFIX . '.access_token_extractor';

    private function __construct() {}
}
