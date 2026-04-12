<?php

declare(strict_types=1);

namespace GaaraHyperf\Exception;

/**
 * 未认证异常.
 */
class UnauthenticatedException extends AuthenticationException
{
    public function __construct(
        string $userIdentifier = ''
    ) {
        parent::__construct(
            message: 'Unauthenticated',
            userIdentifier: $userIdentifier,
        );
    }
}
