<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Lzpeng\HyperfAuthGuard\Exception\UnauthenticatedException;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * X509 认证器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class X509Authenticator extends AbstractAuthenticator
{
    public function __construct(
        private UserProviderInterface $userProvider,
        ?AuthenticationSuccessHandlerInterface $successHandler,
        ?AuthenticationFailureHandlerInterface $failureHandler,
        private array $options,
    ) {
        parent::__construct($successHandler, $failureHandler);
    }

    /**
     * @inheritDoc
     */
    public function supports(ServerRequestInterface $request): bool
    {
        return $this->extractUserIdentifier($request) !== null;
    }

    /**
     * @inheritDoc
     */
    public function authenticate(ServerRequestInterface $request): Passport
    {
        $identifier = $this->extractUserIdentifier($request);
        if (is_null($identifier)) {
            throw new UnauthenticatedException();
        }

        $user = $this->userProvider->findByIdentifier($identifier);
        if (is_null($user)) {
            throw new UnauthenticatedException();
        }

        return new Passport(
            $user->getIdentifier(),
            fn() => $user,
        );
    }

    /**
     * @inheritDoc
     */
    public function isInteractive(): bool
    {
        return false;
    }

    /**
     * 从请求中提取用户标识符
     *
     * @param ServerRequestInterface $request
     * @return string|null
     */
    private function extractUserIdentifier(ServerRequestInterface $request): ?string
    {
        $identifier = null;
        if ($request->hasHeader($this->options['email_param'])) {
            $identifier = $request->getHeaderLine($this->options['email_param']);
        } elseif ($request->hasHeader($this->options['common_name_param'])) {
            $identifier = $request->getHeaderLine($this->options['common_name_param']);
        }

        return $identifier;
    }
}
