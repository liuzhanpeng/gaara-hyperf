<?php

declare(strict_types=1);

namespace GaaraHyperf\OpaqueTokenManager\OpaqueTokenResponder;

use GaaraHyperf\Config\CustomConfig;
use Hyperf\Contract\ContainerInterface;
use InvalidArgumentException;

class OpaqueTokenResponderFactory
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    public function create(array $config): OpaqueTokenResponderInterface
    {
        $type = $config['type'] ?? 'body';
        unset($config['type']);

        switch ($type) {
            case 'cookie':
                return $this->container->make(CookieOpaqueTokenResponder::class, [
                    'cookieName' => $config['cookie_name'] ?? 'access_token',
                    'cookiePath' => $config['cookie_path'] ?? '/',
                    'cookieDomain' => $config['cookie_domain'] ?? '',
                    'cookieSecure' => $config['cookie_secure'] ?? true,
                    'cookieHttpOnly' => $config['cookie_http_only'] ?? true,
                    'cookieSameSite' => $config['cookie_same_site'] ?? 'lax',
                    'template' => $config['template'] ?? null,
                ]);
            case 'body':
                return $this->container->make(BodyOpaqueTokenResponder::class, [
                    'template' => $config['template'] ?? null,
                ]);
            case 'custom':
                $customConfig = CustomConfig::from($config);

                $opaqueTokenResponder = $this->container->make($customConfig->class(), $customConfig->params());
                if (! $opaqueTokenResponder instanceof OpaqueTokenResponderInterface) {
                    throw new InvalidArgumentException(sprintf('The custom OpaqueTokenResponder must implement %s.', OpaqueTokenResponderInterface::class));
                }

                return $opaqueTokenResponder;
            default:
                throw new InvalidArgumentException("Opaque Token Responder type does not exist: {$type}");
        }
    }
}
