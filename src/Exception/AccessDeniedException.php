<?php

declare(strict_types=1);

namespace GaaraHyperf\Exception;

use Exception;
use GaaraHyperf\Token\TokenInterface;

/**
 * 访问被拒绝异常.
 */
class AccessDeniedException extends Exception
{
    public function __construct(
        private TokenInterface $token,
        private mixed $object,
        private mixed $action = null
    ) {
        parent::__construct('access denied');
    }

    /**
     * 返回用户令牌.
     */
    public function getToken(): TokenInterface
    {
        return $this->token;
    }

    /**
     * 返回访问对象.
     */
    public function getObject(): mixed
    {
        return $this->object;
    }

    /**
     * 返回访问动作.
     */
    public function getAction(): mixed
    {
        return $this->action;
    }
}
