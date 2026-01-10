<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\Exception\InvalidCredentialsException;
use Psr\Http\Message\ServerRequestInterface;
use GaaraHyperf\Exception\InvalidPasswordException;
use GaaraHyperf\Exception\UserNotFoundException;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\Passport\PasswordBadge;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * JSON登录认证
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class JsonLoginAuthenticator extends AbstractAuthenticator
{
    /**
     * @param string $checkPath
     * @param string $usernameField
     * @param string $passwordField
     * @param string|\Closure $errorMessage
     * @param UserProviderInterface $userProvider
     * @param AuthenticationSuccessHandlerInterface|null $successHandler
     * @param AuthenticationFailureHandlerInterface|null $failureHandler
     */
    public function __construct(
        private string $checkPath,
        private string $usernameField,
        private string $passwordField,
        private string|\Closure $errorMessage,
        private UserProviderInterface $userProvider,
        ?AuthenticationSuccessHandlerInterface $successHandler,
        ?AuthenticationFailureHandlerInterface $failureHandler,
    ) {
        parent::__construct($successHandler, $failureHandler);
        if (empty($this->checkPath)) {
            throw new \InvalidArgumentException('The "check_path" option must not be empty.');
        }

        if (empty($this->usernameField)) {
            throw new \InvalidArgumentException('The "username_field" option must not be empty.');
        }

        if (empty($this->passwordField)) {
            throw new \InvalidArgumentException('The "password_field" option must not be empty.');
        }

        if (empty($this->errorMessage)) {
            throw new \InvalidArgumentException('The "error_message" option must not be empty.');
        }
    }

    /**
     * @inheritDoc
     */
    public function supports(ServerRequestInterface $request): bool
    {
        $contentType = strtolower($request->getHeaderLine('Content-Type'));

        return str_starts_with($contentType, 'application/json')
            && $request->getUri()->getPath() === $this->checkPath
            && $request->getMethod() === 'POST';
    }

    /**
     * @inheritDoc
     */
    public function authenticate(ServerRequestInterface $request): Passport
    {
        $credientials = $this->getCredentials($request);

        $passport = new Passport(
            $credientials['username'],
            $this->userProvider->findByIdentifier(...),
            [
                new PasswordBadge($credientials['password']),
            ]
        );

        return $passport;
    }

    /**
     * @inheritDoc
     * @override
     */
    public function onAuthenticationFailure(string $guardName, ServerRequestInterface $request, AuthenticationException $exception, ?Passport $passport = null): ?ResponseInterface
    {
        if (!is_null($this->failureHandler)) {
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

        $response = new \Hyperf\HttpMessage\Server\Response();
        return $response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(new \Hyperf\HttpMessage\Stream\SwooleStream(json_encode([
                'error' => $msg,
            ], JSON_UNESCAPED_UNICODE)));
    }

    /**
     * @inheritDoc
     */
    public function isInteractive(): bool
    {
        return true;
    }

    /**
     * 获取认证凭证
     *
     * @param ServerRequestInterface $request
     * @return array
     */
    private function getCredentials(ServerRequestInterface $request): array
    {
        $credientials = [];
        $username = $request->getParsedBody()[$this->usernameField] ?? '';
        if (!is_string($username) || empty($username)) {
            throw new UserNotFoundException(
                message: 'Username is missing',
                userIdentifier: $username,
            );
        }
        $credientials['username'] = trim($username);

        $password = $request->getParsedBody()[$this->passwordField] ?? '';
        if (!is_string($password) || empty($password)) {
            throw new InvalidPasswordException(
                message: 'Password is missing',
                userIdentifier: $credientials['username'],
            );
        }
        $credientials['password'] = trim($password);

        return $credientials;
    }
}
