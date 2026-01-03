<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use Psr\Http\Message\ServerRequestInterface;
use GaaraHyperf\Exception\InvalidPasswordException;
use GaaraHyperf\Exception\UserNotFoundException;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\Passport\PasswordBadge;
use GaaraHyperf\UserProvider\UserProviderInterface;

/**
 * JSON登录认证
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class JsonLoginAuthenticator extends AbstractAuthenticator
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
        $contentType = strtolower($request->getHeaderLine('Content-Type'));

        return str_starts_with($contentType, 'application/json')
            && $request->getUri()->getPath() === $this->options['check_path']
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
        $username = $request->getParsedBody()[$this->options['username_param']] ?? '';
        if (!is_string($username) || empty($username)) {
            throw new UserNotFoundException();
        }
        $credientials['username'] = trim($username);

        $password = $request->getParsedBody()[$this->options['password_param']] ?? '';
        if (!is_string($password) || empty($password)) {
            throw new InvalidPasswordException($username);
        }
        $credientials['password'] = trim($password);

        return $credientials;
    }
}
