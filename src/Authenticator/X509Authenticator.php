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
        // 提取SSL_CLIENT_S_DN中，指定identifier_field的值
        $sslClientSDN = $request->getHeaderLine($this->options['ssl_client_s_dn_param']);
        if (!empty($sslClientSDN)) {
            $identifierField = $this->options['identifier_field'];

            // 兼容 email -> emailAddress
            if (strtolower($identifierField) === 'email') {
                $identifierField = 'emailAddress';
            }

            $identifier = $this->extractFieldFromDn($sslClientSDN, $identifierField);
        }

        return $identifier;
    }

    /**
     * 从 DN 字符串中提取指定字段
     * 支持格式:
     * 1. /C=CN/CN=Alice/emailAddress=a@b.com (OpenSSL 旧版)
     * 2. emailAddress=a@b.com,CN=Alice,C=CN (RFC 2253/4514)
     *
     * @param string $dn
     * @param string $field
     * @return string|null
     */
    private function extractFieldFromDn(string $dn, string $field): ?string
    {
        // 匹配 pattern:  /FIELD=xxx  或  ,FIELD=xxx  或  ^FIELD=xxx
        // 兼容字段名大小写 (i)
        // 排除分隔符 / 和 ,
        $pattern = '/(?:^|[\/,])\s*' . preg_quote($field, '/') . '=([^/,]+)/i';

        if (preg_match($pattern, $dn, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
