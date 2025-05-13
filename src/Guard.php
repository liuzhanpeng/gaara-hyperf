<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorResolverInterface;
use Lzpeng\HyperfAuthGuard\Authorization\AccessDeniedHandlerInterface;
use Lzpeng\HyperfAuthGuard\Authorization\AuthorizationCheckerInterface;
use Lzpeng\HyperfAuthGuard\Event\AuthenticatedTokenCreatedEvent;
use Lzpeng\HyperfAuthGuard\Event\AuthenticationFailureEvent;
use Lzpeng\HyperfAuthGuard\Event\AuthenticationSuccessEvent;
use Lzpeng\HyperfAuthGuard\Event\CheckPassportEvent;
use Lzpeng\HyperfAuthGuard\Exception\AccessDeniedException;
use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Exception\UnauthenticatedException;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\Token\AuthenticatedToken;
use Lzpeng\HyperfAuthGuard\Token\TokenContextInterface;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Lzpeng\HyperfAuthGuard\TokenStorage\TokenStorageInterface;
use Lzpeng\HyperfAuthGuard\TokenStorage\TokenStorageResolverInterface;
use Lzpeng\HyperfAuthGuard\UnauthenticatedHandler\UnauthenticatedHandlerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 认证守卫
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class Guard implements GuardInterface
{
    public function __construct(
        private string $name,
        private AuthenticatorResolverInterface $authenticatorResolver,
        private TokenContextInterface $tokenContext,
        private TokenStorageResolverInterface $tokenStorageResolver,
        private UnauthenticatedHandlerInterface $unauthenticatedHandler,
        private AuthorizationCheckerInterface $authorizationChecker,
        private AccessDeniedHandlerInterface $accessDeniedHandler,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * 返回认证守卫名称
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 认证 
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface|null
     */
    public function authenticate(ServerRequestInterface $request): ?ResponseInterface
    {
        $token = $this->getTokenStorage()->get($this->name);
        $this->tokenContext->setToken($token);

        foreach ($this->authenticatorResolver->getAuthenticatorIds($this->name) as $authenticatorId) {
            $authenticator = $this->authenticatorResolver->resolve($authenticatorId);
            if (!$authenticator->supports($request)) {
                continue;
            }

            $response = $this->executeAuthenticator($authenticator, $request);
            if (!is_null($response)) {
                return $response;
            }
        }

        $token = $this->tokenContext->getToken();
        if (is_null($token) || !$token instanceof AuthenticatedToken) {
            return $this->unauthenticatedHandler->handle($request, UnauthenticatedException::from($token));
        }

        $attribute = $request->getAttribute('authorization_attribute');
        $subject = $request->getAttribute('authorization_subject');
        $request->withoutAttribute('authorization_attribute')->withoutAttribute('authorization_subject');
        if (!$this->authorizationChecker->check($token, $attribute, $subject)) {
            return $this->accessDeniedHandler->handle($request, AccessDeniedException::from($token, $attribute, $subject));
        }

        return null;
    }

    /**
     * 执行指定的认证器认证逻辑
     *
     * @param AuthenticatorInterface $authenticator
     * @param ServerRequestInterface $request
     * @return ResponseInterface|null
     */
    private function executeAuthenticator(AuthenticatorInterface $authenticator, ServerRequestInterface $request): ?ResponseInterface
    {
        try {
            $passport = $authenticator->authenticate($request);
            $checkPassportEvent = new CheckPassportEvent($authenticator, $passport);
            $this->eventDispatcher->dispatch($checkPassportEvent);

            foreach ($passport->getBadges() as $badge) {
                if (!$badge->isResolved()) {
                    throw new AuthenticationException('Credential not resolved');
                }
            }

            $token = $authenticator->createToken($passport, $this->name);
            $token = $this->eventDispatcher->dispatch(new AuthenticatedTokenCreatedEvent($passport, $token))->getToken();

            return $this->handleAuthenticationSuccess($token, $request, $authenticator, $passport);
        } catch (AuthenticationException $exception) {
            return $this->handleAuthenticationFailure($exception, $request, $authenticator, $passport);
        }
    }

    /**
     * 处理认证成功
     *
     * @param TokenInterface $token
     * @param ServerRequestInterface $request
     * @param AuthenticatorInterface $authenticator
     * @param Passport $passport
     * @return ResponseInterface|null
     */
    public function handleAuthenticationSuccess(TokenInterface $token, ServerRequestInterface $request, AuthenticatorInterface $authenticator, Passport $passport): ?ResponseInterface
    {
        $this->tokenContext->setToken($token);

        $this->getTokenStorage()->set($this->name, $token);

        $response = $authenticator->onAuthenticationSuccess($request, $token);

        $authenticationSuccessEvent = new AuthenticationSuccessEvent(
            $authenticator,
            $passport,
            $token,
            $request,
            $response
        );

        $this->eventDispatcher->dispatch($authenticationSuccessEvent);

        return $authenticationSuccessEvent->getResponse();
    }

    /**
     * 处理认证失败
     *
     * @param AuthenticationException $exception
     * @param ServerRequestInterface $request
     * @param AuthenticatorInterface $authenticator
     * @param Passport|null $passport
     * @return ResponseInterface
     */
    private function handleAuthenticationFailure(AuthenticationException $exception, ServerRequestInterface $request, AuthenticatorInterface $authenticator, ?Passport $passport): ?ResponseInterface
    {
        $response = $authenticator->onAuthenticationFailure($request, $exception, $passport);

        $authenticationFailureEvent = new AuthenticationFailureEvent(
            $authenticator,
            $passport,
            $exception,
            $request,
            $response
        );

        $this->eventDispatcher->dispatch($authenticationFailureEvent);

        return $authenticationFailureEvent->getResponse();
    }

    /**
     * 返回认证守卫的Token存储器
     *
     * @return TokenStorageInterface
     */
    private function getTokenStorage(): TokenStorageInterface
    {
        return $this->tokenStorageResolver->resolve($this->name);
    }
}
