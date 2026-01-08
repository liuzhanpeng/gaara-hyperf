<?php

declare(strict_types=1);

namespace GaaraHyperf\UnauthenticatedHandler;

use Hyperf\Contract\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hyperf\Session\Session;
use GaaraHyperf\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 重定向未认证处理器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class RedirectUnauthenticatedHandler implements UnauthenticatedHandlerInterface
{
    public function __construct(
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
        private SessionInterface $session,
        private array $options,
    ) {
        if (!isset($this->options['target_path'])) {
            throw new \InvalidArgumentException('target_path is required');
        }

        $this->options = array_replace_recursive([
            'redirect_enabled' => true,
            'redirect_param' => 'redirect_to',
            'authentication_error_param' => 'authentication_error',
            'authentication_error_message' => '未认证或已登出，请重新登录'
        ], $this->options);
    }

    public function handle(ServerRequestInterface $request, ?TokenInterface $token): ResponseInterface
    {
        if ($this->session instanceof Session) {
            $this->session->flash($this->options['authentication_error_param'], $this->options['authentication_error_message']);
        }

        $targetPath = $this->options['target_path'];
        if ($this->options['redirect_enabled']) {
            $targetPath .= sprintf('?%s=%s', $this->options['redirect_param'], urlencode($request->getUri()->getPath()));
        }

        return $this->response->redirect($targetPath);
    }
}
