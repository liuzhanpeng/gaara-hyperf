<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\LoginThrottler;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Config\LoginThrottlerConfig;

/**
 * 登录限流器工厂
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class LoginThrottlerFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function create(LoginThrottlerConfig $loginThrottlerConfig): LoginThrottlerInterface
    {
        $type = $loginThrottlerConfig->type();
        $options = $loginThrottlerConfig->options();

        switch ($type) {
            case 'sliding_window':
                return $this->container->make(SlidingWindowLoginThrotter::class, [
                    'options' => $options
                ]);
            default:
                throw new \InvalidArgumentException("不支持的登录限流器类型: $type");
        }
    }
}
