<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator\Builder;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticationFailureHandlerInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticationSuccessHandlerInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorBuilderInterface;

/**
 * 抽象认证器构建器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
abstract class AbstractAuthenticatorBuilder implements AuthenticatorBuilderInterface
{
    public function __construct(
        protected ContainerInterface $container,
    ) {}

    /**
     * 创建SuccessHandler
     *
     * @param array $options
     * @return AuthenticationSuccessHandlerInterface|null
     */
    protected function createSuccessHandler(array $options): ?AuthenticationSuccessHandlerInterface
    {
        if (!isset($options['success_handler'])) {
            return null;
        }

        $successHandlerOption = $options['success_handler'];
        unset($options['success_handler']);
        if (is_string($successHandlerOption)) {
            $successHandlerOption = [
                'class' => $successHandlerOption,
            ];
        }

        $successHandler = $this->container->make(
            $successHandlerOption['class'],
            $successHandlerOption['args'] ?? []
        );

        if (!$successHandler instanceof AuthenticationSuccessHandlerInterface) {
            throw new \InvalidArgumentException(sprintf('%s must implement %s', $successHandlerOption['class'], AuthenticationSuccessHandlerInterface::class));
        }

        return $successHandler;
    }

    /**
     * 创建FailureHandler
     *
     * @param array $options
     * @return AuthenticationFailureHandlerInterface|null
     */
    protected function createFailureHandler(array $options): ?AuthenticationFailureHandlerInterface
    {
        if (!isset($options['failure_handler'])) {
            return null;
        }

        $failureHandlerOption = $options['failure_handler'];
        unset($options['failure_handler']);
        if (is_string($failureHandlerOption)) {
            $failureHandlerOption = [
                'class' => $failureHandlerOption,
            ];
        }

        $failureHandler = $this->container->make(
            $failureHandlerOption['class'],
            $failureHandlerOption['args'] ?? []
        );

        if (!$failureHandler instanceof AuthenticationFailureHandlerInterface) {
            throw new \InvalidArgumentException(sprintf('%s must implement %s', $failureHandlerOption['class'], AuthenticationFailureHandlerInterface::class));
        }

        return $failureHandler;
    }
}
