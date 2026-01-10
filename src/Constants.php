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
    public const __PREFIX = 'gaara';

    public const TOKEN_CONTEXT_PREFIX = self::__PREFIX . '.token_context';

    public const REQUEST_AUTHORIZATION_ATTRIBUTE = self::__PREFIX . '.authorization_attribute';
    public const REQUEST_AUTHORIZATION_SUBJECT = self::__PREFIX . '.authorization_subject';

    public const GUARD_PREFIX = self::__PREFIX . '.guard';

    private function __construct() {}
}
