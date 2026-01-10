<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator\Builder;

use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Authenticator\JsonLoginAuthenticator;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * JSON登录认证器构建器
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class JsonLoginAuthenticatorBuilder extends AbstractAuthenticatorBuilder
{
    public function create(array $options, UserProviderInterface $userProvider, EventDispatcher $eventDispatcher): AuthenticatorInterface
    {
        if (!isset($options['check_path'])) {
            throw new \InvalidArgumentException('The "check_path" option must be set.');
        }

        return new JsonLoginAuthenticator(
            checkPath: $options['check_path'],
            usernameField: $options['username_field'] ?? 'username',
            passwordField: $options['password_field'] ?? 'password',
            errorMessage: $options['error_message'] ?? '用户名或密码错误',
            userProvider: $userProvider,
            successHandler: $this->createSuccessHandler($options),
            failureHandler: $this->createFailureHandler($options),
        );
    }
}
