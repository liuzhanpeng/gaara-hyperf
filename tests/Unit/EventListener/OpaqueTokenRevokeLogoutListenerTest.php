<?php

declare(strict_types=1);

use GaaraHyperf\AccessTokenExtractor\AccessTokenExtractorInterface;
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

it('subscribes logout event with normal priority', function (): void {
    $events = OpaqueTokenRevokeLogoutListener::getSubscribedEvents();

    expect($events[LogoutEvent::class])->toBe(['onLogout', Priority::NORMAL]);
});

it('does nothing when request method is not post', function (): void {
    /** @var MockInterface&OpaqueTokenManagerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);
    /** @var AccessTokenExtractorInterface&MockInterface $extractor */
    $extractor = Mockery::mock(AccessTokenExtractorInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    $request->shouldReceive('getMethod')->once()->andReturn('GET');
    $extractor->shouldNotReceive('extract');
    $manager->shouldNotReceive('revoke');

    $listener = new OpaqueTokenRevokeLogoutListener($manager, $extractor);
    $listener->onLogout(new LogoutEvent($token, $request));

    expect(true)->toBeTrue();
});

it('does nothing when extracted access token is null', function (): void {
    /** @var MockInterface&OpaqueTokenManagerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);
    /** @var AccessTokenExtractorInterface&MockInterface $extractor */
    $extractor = Mockery::mock(AccessTokenExtractorInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    $request->shouldReceive('getMethod')->once()->andReturn('POST');
    $extractor->shouldReceive('extract')->once()->with($request)->andReturn(null);
    $manager->shouldNotReceive('revoke');

    $listener = new OpaqueTokenRevokeLogoutListener($manager, $extractor);
    $listener->onLogout(new LogoutEvent($token, $request));

    expect(true)->toBeTrue();
});

it('revokes extracted access token on post logout', function (): void {
    /** @var MockInterface&OpaqueTokenManagerInterface $manager */
    $manager = Mockery::mock(OpaqueTokenManagerInterface::class);
    /** @var AccessTokenExtractorInterface&MockInterface $extractor */
    $extractor = Mockery::mock(AccessTokenExtractorInterface::class);
    /** @var MockInterface&TokenInterface $token */
    $token = Mockery::mock(TokenInterface::class);
    /** @var MockInterface&ServerRequestInterface $request */
    $request = Mockery::mock(ServerRequestInterface::class);

    $request->shouldReceive('getMethod')->once()->andReturn('POST');
    $extractor->shouldReceive('extract')->once()->with($request)->andReturn('opaque-token');
    $manager->shouldReceive('revoke')->once()->with('opaque-token');

    $listener = new OpaqueTokenRevokeLogoutListener($manager, $extractor);
    $listener->onLogout(new LogoutEvent($token, $request));

    expect(true)->toBeTrue();
});
