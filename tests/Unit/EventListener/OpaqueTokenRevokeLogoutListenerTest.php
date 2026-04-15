<?php

declare(strict_types=1);

use GaaraHyperf\Event\LogoutEvent;
use GaaraHyperf\EventListener\OpaqueTokenRevokeLogoutListener;
use GaaraHyperf\EventListener\Priority;
use GaaraHyperf\OpaqueTokenManager\OpaqueTokenManagerInterface;
use GaaraHyperf\Token\TokenInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

afterEach(function (): void {
    Mockery::close();
});

it('subscribes to LogoutEvent with normal priority', function (): void {
    $events = OpaqueTokenRevokeLogoutListener::getSubscribedEvents();

    expect($events[LogoutEvent::class])->toBe(['onLogout', Priority::NORMAL]);
});

it('does nothing when request method is not POST', function (): void {
    /** @var MockInterface&OpaqueTokenManagerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    $request->shouldReceive('getMethod')->once()->andReturn('GET');
    $manager->shouldNotReceive('revoke');

    (new OpaqueTokenRevokeLogoutListener($manager))->onLogout(new LogoutEvent($token, $request));
});

it('revokes token via manager on POST logout', function (): void {
    /** @var MockInterface&OpaqueTokenManagerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    $request->shouldReceive('getMethod')->once()->andReturn('POST');
    $manager->shouldReceive('revoke')->once()->with($request);

    (new OpaqueTokenRevokeLogoutListener($manager))->onLogout(new LogoutEvent($token, $request));
});
