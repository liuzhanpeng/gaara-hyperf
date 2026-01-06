<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator\Builder;

use Hyperf\Contract\ContainerInterface;
use GaaraHyperf\Authenticator\AuthenticationFailureHandlerInterface;
use GaaraHyperf\Authenticator\AuthenticationSuccessHandlerInterface;
use GaaraHyperf\Authenticator\AuthenticatorBuilderInterface;

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

        if (is_string($options['success_handler'])) {
            $options['success_handler'] = [
                'class' => $options['success_handler'],
            ];
        }

        $params = $options['success_handler']['params'] ?? [];
        if (count($params) > 0) {
            $params = array_combine(
                array_map(fn($key) => lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key)))), array_keys($params)),
                $params
            );
        }

        $successHandler = $this->container->make(
            $options['success_handler']['class'],
            $params
        );

        if (!$successHandler instanceof AuthenticationSuccessHandlerInterface) {
            throw new \InvalidArgumentException(sprintf('%s must implement %s', $options['success_handler']['class'], AuthenticationSuccessHandlerInterface::class));
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

        if (!is_array($options['failure_handler'])) {
            $options['failure_handler'] = [
                'class' => $options['failure_handler'],
            ];
        }

        $params = $options['failure_handler']['params'] ?? [];
        if (count($params) > 0) {
            $params = array_combine(
                array_map(fn($key) => lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key)))), array_keys($params)),
                $params
            );
        }

        $failureHandler = $this->container->make(
            $options['failure_handler']['class'],
            $params
        );

        if (!$failureHandler instanceof AuthenticationFailureHandlerInterface) {
            throw new \InvalidArgumentException(sprintf('%s must implement %s', $options['failure_handler']['class'], AuthenticationFailureHandlerInterface::class));
        }

        return $failureHandler;
    }
}
