<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator\Builder;

use Hyperf\Contract\SessionInterface;
use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Authenticator\FormLoginAuthenticator;
use GaaraHyperf\CsrfTokenManager\CsrfTokenManagerResolverInterface;
use GaaraHyperf\EventListener\CsrfTokenBadgeCheckListener;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * 表单登录认证器构建器
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class FormLoginAuthenticatorBuilder extends AbstractAuthenticatorBuilder
{
    public function create(array $options, UserProviderInterface $userProvider, EventDispatcher $eventDispatcher): AuthenticatorInterface
    {
        if (!isset($options['check_path'])) {
            throw new \InvalidArgumentException('The "check_path" option must be set.');
        }

        if (isset($options['csrf_enabled']) && $options['csrf_enabled']) {
            $csrfTokenManager = $this->container->get(CsrfTokenManagerResolverInterface::class)->resolve($options['csrf_token_manager'] ?? 'default');
            $eventDispatcher->addSubscriber(new CsrfTokenBadgeCheckListener($csrfTokenManager));
        }

        return new FormLoginAuthenticator(
            checkPath: $options['check_path'],
            targetPath: $options['target_path'] ?? '/',
            failurePath: $options['failure_path'] ?? '/',
            usernameField: $options['username_field'] ?? 'username',
            passwordField: $options['password_field'] ?? 'password',
            redirectEnabled: $options['redirect_enabled'] ?? true,
            redirectField: $options['redirect_field'] ?? 'redirect_to',
            csrfEnabled: $options['csrf_enabled'] ?? true,
            csrfField: $options['csrf_field'] ?? '_csrf_token',
            csrfId: $options['csrf_id'] ?? 'authenticate',
            errorMessage: $options['error_message'] ?? '用户名或密码错误',
            userProvider: $userProvider,
            response: $this->container->get(\Hyperf\HttpServer\Contract\ResponseInterface::class),
            session: $this->container->get(SessionInterface::class),
            successHandler: $this->createSuccessHandler($options),
            failureHandler: $this->createFailureHandler($options),
        );
    }
}
