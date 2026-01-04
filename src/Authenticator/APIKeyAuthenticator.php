<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use Psr\Http\Message\ServerRequestInterface;
use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\Exception\InvalidAPIKeyException;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\UserProvider\UserProviderInterface;

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
            throw new AuthenticationException($apiKey, 'API key is missing');
        }

        $user = $this->userProvider->findByIdentifier($apiKey);
        if (is_null($user)) {
            throw new InvalidAPIKeyException($apiKey, 'Invalid API key');
        }

        return new Passport($apiKey, fn() => $user);
    }

    /**
     * @inheritDoc
     */
    public function isInteractive(): bool
    {
        return false;
    }
}
