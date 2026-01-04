<?php

declare(strict_types=1);

namespace GaaraHyperf\EventListener;

use GaaraHyperf\Event\AuthenticationFailureEvent;
use GaaraHyperf\Event\AuthenticationSuccessEvent;
use GaaraHyperf\Event\LogoutEvent;
use GaaraHyperf\IPResolver\IPResolverInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * 审计日志监听器
 * 
 * 基于Logger记录认证成功和失败的审计日志
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuditLogListener implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private IPResolverInterface $ipResolver,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            AuthenticationSuccessEvent::class => 'onAuthenticationSuccess',
            AuthenticationFailureEvent::class => 'onAuthenticationFailure',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $authenticator = $event->getAuthenticator();
        $token = $event->getToken();
        $request = $event->getRequest();

        $this->logger->log(LogLevel::INFO, 'Authentication success', [
            'guard' => $token->getGuardName(),
            'authenticator' => get_class($authenticator),
            'user_identifier' => $token->getUserIdentifier(),
            'request_uri' => (string)$request->getUri(),
            'ip' => $this->ipResolver->resolve($request),
            'user_agent' => $request->getHeaderLine('User-Agent'),
            'occurred_at' => date('c'),
        ]);
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $authenticator = $event->getAuthenticator();
        $request = $event->getRequest();
        $exception = $event->getException();

        $this->logger->log(LogLevel::ERROR, 'Authentication failure', [
            'guard' => $event->getGuardName(),
            'authenticator' => get_class($authenticator),
            'user_identifier' => $exception->getUserIdentifier(),
            'request_uri' => (string)$request->getUri(),
            'ip' => $this->ipResolver->resolve($request),
            'user_agent' => $request->getHeaderLine('User-Agent'),
            'exception_type' => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'occurred_at' => date('c'),
        ]);
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        $request = $event->getRequest();

        $this->logger->log(LogLevel::INFO, 'User logout', [
            'guard' => $token->getGuardName(),
            'user_identifier' => $token->getUserIdentifier(),
            'request_uri' => (string)$request->getUri(),
            'ip' => $this->ipResolver->resolve($request),
            'user_agent' => $request->getHeaderLine('User-Agent'),
            'occurred_at' => date('c'),
        ]);
    }
}
