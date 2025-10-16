<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Psr\Http\Message\ServerRequestInterface;
use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;

/**
 * API Key认证器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class APIKeyAuthenticator extends AbstractAuthenticator
{
    /**
     * @param UserProviderInterface $userProvider
     * @param array $options
     * @param AuthenticationSuccessHandlerInterface|null $successHandler
     * @param AuthenticationFailureHandlerInterface|null $failureHandler
     */
    public function __construct(
        private UserProviderInterface $userProvider,
        private array $options,
        ?AuthenticationSuccessHandlerInterface $successHandler,
        ?AuthenticationFailureHandlerInterface $failureHandler,
    ) {
        parent::__construct($successHandler, $failureHandler);
    }

    /**
     * @inheritDoc
     */
    public function supports(ServerRequestInterface $request): bool
    {
        return !empty($request->getHeaderLine($this->options['api_key_param']));
    }

    /**
     * @inheritDoc
     */
    public function authenticate(ServerRequestInterface $request): Passport
    {
        $apiKey = $request->getHeaderLine($this->options['api_key_param']);
        if (empty($apiKey)) {
            throw new AuthenticationException('API key is missing', $apiKey);
        }

        return new Passport(
            $apiKey,
            $this->userProvider->findByIdentifier(...),
        );
    }

    /**
     * @inheritDoc
     */
    public function isInteractive(): bool
    {
        return false;
    }
}
