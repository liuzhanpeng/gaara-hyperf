<?php

declare(strict_types=1);

namespace GaaraHyperf\RequestMatcher;

use Hyperf\Contract\ContainerInterface;
use GaaraHyperf\Config\ComponentConfig;
use GaaraHyperf\Config\CustomConfig;

/**
 * 请求匹配器工厂
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class RequestMatcherFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function create(ComponentConfig $config): RequestMatcherInterface
    {
        $type = $config->type();
        $options = $config->options();

        switch ($type) {
            case 'default':
                if (!isset($options['pattern']) || empty($options['pattern'])) {
                    throw new \InvalidArgumentException('pattern option is required for default request matcher');
                }

                return $this->container->make(RequestMatcher::class, [
                    'pattern' => $options['pattern'],
                    'logoutPath' => $options['logout_path'] ?? null,
                    'exclusions' => $options['exclusions'] ?? [],
                ]);
            case 'custom':
                $customConfig = CustomConfig::from($options);

                $requestMatcher = $this->container->make($customConfig->class(), $customConfig->args());
                if (!$requestMatcher instanceof RequestMatcherInterface) {
                    throw new \InvalidArgumentException(sprintf('Request Matcher "%s" must implement %s', $customConfig->class(), RequestMatcherInterface::class));
                }

                return $requestMatcher;

            default:
                throw new \InvalidArgumentException(sprintf('Unsupported request matcher type: %s', $type));
        }
    }
}
