<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use Closure;
use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\Exception\InvalidCredentialsException;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\Passport\PasswordBadge;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Hyperf\HttpMessage\Server\Response;
use Hyperf\HttpMessage\Stream\SwooleStream;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * JSON登录认证
 */
class JsonLoginAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private string $checkPath,
        private string $usernameField,
        private string $passwordField,
        private int $failureHttpStatusCode,
        private string $errorField,
        private Closure|string $errorMessage,
        private UserProviderInterface $userProvider,
        ?AuthenticationSuccessHandlerInterface $successHandler,
        ?AuthenticationFailureHandlerInterface $failureHandler,
    ) {
        parent::__construct($successHandler, $failureHandler);
        if (empty($this->checkPath)) {
            throw new InvalidArgumentException('The "check_path" option must not be empty.');
        }

        if (empty($this->usernameField)) {
            throw new InvalidArgumentException('The "username_field" option must not be empty.');
        }

        if (empty($this->passwordField)) {
            throw new InvalidArgumentException('The "password_field" option must not be empty.');
        }

        if (empty($this->errorField)) {
            throw new InvalidArgumentException('The "error_field" option must not be empty.');
        }

        if (empty($this->errorMessage)) {
            throw new InvalidArgumentException('The "error_message" option must not be empty.');
        }
    }

    public function supports(ServerRequestInterface $request): bool
    {
        $contentType = strtolower($request->getHeaderLine('Content-Type'));

        return str_starts_with($contentType, 'application/json')
            && $request->getUri()->getPath() === $this->checkPath
            && $request->getMethod() === 'POST';
    }

    public function authenticate(ServerRequestInterface $request): Passport
    {
        $credientials = $this->getCredentials($request);

        return new Passport(
            $credientials['username'],
            $this->userProvider->findByIdentifier(...),
            [
                new PasswordBadge($credientials['password']),
            ]
        );
    }

    /**
     * @override
     */
    public function onAuthenticationFailure(string $guardName, ServerRequestInterface $request, AuthenticationException $exception, ?Passport $passport = null): ?ResponseInterface
    {
        if (! is_null($this->failureHandler)) {
            return $this->failureHandler->handle($guardName, $request, $exception, $passport);
        }

        if (is_callable($this->errorMessage)) {
            $msg = ($this->errorMessage)($exception);
        } else {
            if ($exception instanceof InvalidCredentialsException) {
                $msg = $this->errorMessage;
            } else {
                $msg = $exception->getMessage();
            }
        }

        $response = new Response();
        return $response->withStatus($this->failureHttpStatusCode)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(new SwooleStream(json_encode([
                $this->errorField => $msg,
            ], JSON_UNESCAPED_UNICODE)));
    }

    public function isInteractive(): bool
    {
        return true;
    }

    /**
     * 获取认证凭证
     */
    private function getCredentials(ServerRequestInterface $request): array
    {
        $credientials = [];
        $username = $request->getParsedBody()[$this->usernameField] ?? '';
        if (! is_string($username) || empty($username)) {
            throw new InvalidCredentialsException(
                message: 'Username is missing',
            );
        }
        $credientials['username'] = trim($username);

        $password = $request->getParsedBody()[$this->passwordField] ?? '';
        if (! is_string($password) || empty($password)) {
            throw new InvalidCredentialsException(
                message: 'Password is missing',
                userIdentifier: $credientials['username'],
            );
        }
        $credientials['password'] = trim($password);

        return $credientials;
    }
}
