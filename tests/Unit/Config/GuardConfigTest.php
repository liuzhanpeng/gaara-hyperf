<?php

declare(strict_types=1);

use GaaraHyperf\Authorization\DefaultAccessDeniedHandler;
use GaaraHyperf\Authorization\HttpAuthorizationRuleResolver;
use GaaraHyperf\Authorization\NullAuthorizationChecker;
use GaaraHyperf\Config\GuardConfig;

it('creates guard config and applies defaults', function (): void {
    $guardConfig = GuardConfig::from([
        'matcher' => ['pattern' => '/api/*'],
        'user_provider' => ['type' => 'memory', 'users' => []],
        'authenticators' => [
            'api_key' => ['api_key_field' => 'X-API-KEY'],
        ],
    ]);

    expect($guardConfig->requestMatcherConfig()->type())->toBe('default')
        ->and($guardConfig->requestMatcherConfig()->options())->toBe(['pattern' => '/api/*'])
        ->and($guardConfig->userProviderConfig()->type())->toBe('memory')
        ->and($guardConfig->tokenStorageConfig()->type())->toBe('null')
        ->and($guardConfig->unauthenticatedHandlerConfig()->type())->toBe('default')
        ->and($guardConfig->authorizationRuleResolverConfig()->class())->toBe(HttpAuthorizationRuleResolver::class)
        ->and($guardConfig->authorizationCheckerConfig()->class())->toBe(NullAuthorizationChecker::class)
        ->and($guardConfig->accessDeniedHandlerConfig()->class())->toBe(DefaultAccessDeniedHandler::class)
        ->and($guardConfig->passwordHasherId())->toBe('default')
        ->and(iterator_to_array($guardConfig->listenerConfigCollection()))->toHaveCount(0)
        ->and(iterator_to_array($guardConfig->authenticatorConfigCollection()))->toHaveCount(1);
});

it('applies authorization defaults when authorization config is missing', function (): void {
    $guardConfig = GuardConfig::from([
        'matcher' => ['pattern' => '/api/*'],
        'user_provider' => ['type' => 'memory', 'users' => []],
        'authenticators' => [
            'api_key' => ['api_key_field' => 'X-API-KEY'],
        ],
    ]);

    expect($guardConfig->authorizationRuleResolverConfig()->class())->toBe(HttpAuthorizationRuleResolver::class)
        ->and($guardConfig->authorizationCheckerConfig()->class())->toBe(NullAuthorizationChecker::class)
        ->and($guardConfig->accessDeniedHandlerConfig()->class())->toBe(DefaultAccessDeniedHandler::class);
});

it('throws when matcher config is missing', function (): void {
    expect(fn () => GuardConfig::from([
        'user_provider' => ['type' => 'memory', 'users' => []],
        'authenticators' => ['api_key' => []],
    ]))->toThrow(InvalidArgumentException::class, 'matcher config is required');
});

it('throws when user provider config is missing', function (): void {
    expect(fn () => GuardConfig::from([
        'matcher' => ['pattern' => '/api/*'],
        'authenticators' => ['api_key' => []],
    ]))->toThrow(InvalidArgumentException::class, 'user_provider config is required');
});

it('throws when authenticators config is missing', function (): void {
    expect(fn () => GuardConfig::from([
        'matcher' => ['pattern' => '/api/*'],
        'user_provider' => ['type' => 'memory', 'users' => []],
    ]))->toThrow(InvalidArgumentException::class, 'authenticators config is required');
});
