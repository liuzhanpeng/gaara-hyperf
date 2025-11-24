<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator\Builder;

use Hyperf\Contract\SessionInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\FormLoginAuthenticator;
use Lzpeng\HyperfAuthGuard\CsrfTokenManager\CsrfTokenManagerResolverInterface;
use Lzpeng\HyperfAuthGuard\EventListener\CsrfTokenBadgeCheckListener;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
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

        $options = array_merge([
            'target_path' => '/',
            'failure_path' => '/login',
            'redirect_enabled' => true,
            'redirect_param' => 'redirect_to',
            'username_param' => 'username',
            'password_param' => 'password',
            'error_message' => '用户名或密码错误',
            'csrf_enabled' => true,
            'csrf_id' => 'authenticate',
            'csrf_param' => '_csrf_token',
            'csrf_token_manager' => 'default',
        ], $options);

        if ($options['csrf_enabled']) {
            $csrfTokenManager = $this->container->get(CsrfTokenManagerResolverInterface::class)->resolve($options['csrf_token_manager']);
            $eventDispatcher->addSubscriber(new CsrfTokenBadgeCheckListener($csrfTokenManager));
        }

        return new FormLoginAuthenticator(
            userProvider: $userProvider,
            response: $this->container->get(\Hyperf\HttpServer\Contract\ResponseInterface::class),
            session: $this->container->get(SessionInterface::class),
            options: $options,
            successHandler: $this->createSuccessHandler($options),
            failureHandler: $this->createFailureHandler($options),
        );
    }
}
