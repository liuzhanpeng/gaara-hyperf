<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use GaaraHyperf\Exception\InvalidCredentialsException;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\UserProvider\UserProviderInterface;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * API Key认证器.
 */
class APIKeyAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private string $apiKeyField,
        private UserProviderInterface $userProvider,
        ?AuthenticationSuccessHandlerInterface $successHandler,
        ?AuthenticationFailureHandlerInterface $failureHandler,
    ) {
        parent::__construct($successHandler, $failureHandler);

        if (empty($this->apiKeyField)) {
            throw new InvalidArgumentException('apiKeyField cannot be empty');
        }
    }

    public function supports(ServerRequestInterface $request): bool
    {
        return ! empty($request->getHeaderLine($this->apiKeyField));
    }

    public function authenticate(ServerRequestInterface $request): Passport
    {
        $apiKey = $request->getHeaderLine($this->apiKeyField);
        if (empty($apiKey)) {
            throw new InvalidCredentialsException(sprintf('API key is missing. Expected header `%s`.', $this->apiKeyField));
        }

        return new Passport(
            $apiKey,
            $this->userProvider->findByIdentifier(...)
        );
    }

    public function isInteractive(): bool
    {
        return false;
    }
}
