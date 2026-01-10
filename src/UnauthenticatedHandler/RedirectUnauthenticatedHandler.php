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
        private string $targetPath,
        private bool $redirectEnabled = true,
        private string $redirectField = 'redirect_to',
        private string $errorField = 'authentication_error',
        private string $errorMessage = '未认证或已登出，请重新登录',
    ) {
        if (empty($this->targetPath)) {
            throw new \InvalidArgumentException('target_path is required');
        }
    }

    public function handle(ServerRequestInterface $request, ?TokenInterface $token): ResponseInterface
    {
        if ($this->session instanceof Session) {
            $this->session->flash($this->errorField, $this->errorMessage);
        }

        $targetPath = $this->targetPath;
        if ($this->redirectEnabled) {
            $targetPath .= sprintf('?%s=%s', $this->redirectField, urlencode($request->getUri()->getPath()));
        }

        return $this->response->redirect($targetPath);
    }
}
