<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use GaaraHyperf\Exception\InvalidCredentialsException;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\UserProvider\UserProviderInterface;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * X509 认证器.
 */
class X509Authenticator extends AbstractAuthenticator
{
    public function __construct(
        private string $sslClientSDNField,
        private string $identifierField,
        private UserProviderInterface $userProvider,
        ?AuthenticationSuccessHandlerInterface $successHandler,
        ?AuthenticationFailureHandlerInterface $failureHandler,
    ) {
        parent::__construct($successHandler, $failureHandler);

        if (empty($this->sslClientSDNField)) {
            throw new InvalidArgumentException('ssl_client_s_dn_field cannot be empty');
        }

        if (empty($this->identifierField)) {
            throw new InvalidArgumentException('identifier_field cannot be empty');
        }
    }

    public function supports(ServerRequestInterface $request): bool
    {
        return $this->extractUserIdentifier($request) !== null;
    }

    public function authenticate(ServerRequestInterface $request): Passport
    {
        $identifier = $this->extractUserIdentifier($request);
        if (is_null($identifier)) {
            throw new InvalidCredentialsException(
                message: 'User identifier not found in client certificate',
                userIdentifier: $identifier ?? '',
            );
        }

        return new Passport(
            $identifier,
            $this->userProvider->findByIdentifier(...),
        );
    }

    public function isInteractive(): bool
    {
        return false;
    }

    /**
     * 从请求中提取用户标识符.
     */
    private function extractUserIdentifier(ServerRequestInterface $request): ?string
    {
        $identifier = null;
        // 提取SSL_CLIENT_S_DN中，指定identifier_field的值
        $sslClientSDN = $request->getHeaderLine($this->sslClientSDNField);
        if (! empty($sslClientSDN)) {
            $identifierField = $this->identifierField;

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
     * 2. emailAddress=a@b.com,CN=Alice,C=CN (RFC 2253/4514).
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
