<?php

declare(strict_types=1);

use GaaraHyperf\Authorization\DefaultAccessDeniedHandler;
use GaaraHyperf\Exception\AccessDeniedException;
use GaaraHyperf\Token\TokenInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

afterEach(function (): void {
    Mockery::close();
});

it('throws access denied exception with context', function (): void {
    $handler = new DefaultAccessDeniedHandler();
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);

    try {
        $handler->handle($request, $token, 'ROLE_ADMIN', 'post:1');
        $this->fail('Expected AccessDeniedException was not thrown');
    } catch (AccessDeniedException $exception) {
        expect($exception->getMessage())->toBe('access denied')
            ->and($exception->getToken())->toBe($token)
            ->and($exception->getObject())->toBe('ROLE_ADMIN')
            ->and($exception->getAction())->toBe('post:1');
    }
});
