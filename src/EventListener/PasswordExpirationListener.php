<?php

declare(strict_types=1);

namespace GaaraHyperf\EventListener;

use GaaraHyperf\Event\AuthenticationSuccessEvent;
use GaaraHyperf\Exception\PasswordExpiredException;
use GaaraHyperf\User\PasswordExpirationAwareUserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * 密码过期监听器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class PasswordExpirationListener implements EventSubscriberInterface
{
    private array $excludedPaths; //  不检查密码过期的路径
    private int $warningDays; // 密码过期前多少天发出警告

    public function __construct(
        private array $params,
    ) {
        $this->excludedPaths = $params['excluded_paths'] ?? [];
        $this->warningDays = $params['warning_days'] ?? 7;
    }

    public static function getSubscribedEvents()
    {
        return [
            AuthenticationSuccessEvent::class => 'onAuthenticationSuccess',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $passport = $event->getPassport();
        $user = $passport->getUser();

        if (!$user instanceof PasswordExpirationAwareUserInterface) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getUri()->getPath();

        if (in_array($path, $this->excludedPaths, true)) {
            return;
        }

        $now = new \DateTimeImmutable();
        $expiresAt = $user->getExpiresAt();
        $expired = $expiresAt <= $now;

        if ($expired) {
            throw new PasswordExpiredException(
                message: 'Password has expired',
                userIdentifier: $user->getIdentifier(),
            );
        }

        $daysUntilExpiry = $now->diff($expiresAt)->days;
        $isExpiringSoon = $daysUntilExpiry <= $this->warningDays;

        if ($isExpiringSoon) {
            $response = $event->getResponse();
            if ($response === null) {
                return;
            }

            $response->headers->set('X-Password-Expiration-Status', 'expiring_soon');
            $response->headers->set('X-Password-Expiration-DateTime', $expiresAt->format('Y-m-d H:i:s'));
        }
    }
}
