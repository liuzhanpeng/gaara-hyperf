<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\UnauthenticatedHandler;

use Lzpeng\HyperfAuthGuard\Exception\UnauthenticatedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 内置的未认证处理器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class UnauthenticatedHandler implements UnauthenticatedHandlerInterface
{
    public function __construct(
        private array $strategies,
    ) {}

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request, UnauthenticatedException $unauthenticatedException): ResponseInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($request, $unauthenticatedException)) {
                return $strategy->handle($request, $unauthenticatedException);
            }
        }

        throw new \RuntimeException('No unauthenticated handling strategy found for this request');
    }
}
