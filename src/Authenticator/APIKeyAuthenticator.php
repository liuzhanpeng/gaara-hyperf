<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\Exception\InvalidCredentialsException;
use GaaraHyperf\Exception\UserNotFoundException;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Hyperf\HttpMessage\Server\Response;
use Hyperf\HttpMessage\Stream\SwooleStream;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
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
            throw new InvalidCredentialsException('API key is missing');
        }

        $user = $this->userProvider->findByIdentifier($apiKey);
        if (is_null($user)) {
            throw new UserNotFoundException(
                message: 'Invalid API key',
                userIdentifier: $apiKey,
            );
        }

        return new Passport($apiKey, fn () => $user);
    }

    /**
     * @override
     */
    public function onAuthenticationFailure(string $guardName, ServerRequestInterface $request, AuthenticationException $exception, ?Passport $passport = null): ?ResponseInterface
    {
        if (! is_null($this->failureHandler)) {
            return $this->failureHandler->handle($guardName, $request, $exception, $passport);
        }

        // 默认返回401响应
        $response = new Response();
        return $response->withStatus(401)->withBody(new SwooleStream($exception->getMessage()));
    }

    public function isInteractive(): bool
    {
        return false;
    }
}
