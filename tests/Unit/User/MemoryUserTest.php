<?php

declare(strict_types=1);

use GaaraHyperf\User\MemoryUser;
use GaaraHyperf\User\UserInterface;
use GaaraHyperf\User\PasswordAwareUserInterface;

describe('MemoryUser', function () {

    it('should implement UserInterface and PasswordAwareUserInterface', function () {
        $user = new MemoryUser('testuser', 'password123');

        expect($user)->toBeInstanceOf(UserInterface::class);
        expect($user)->toBeInstanceOf(PasswordAwareUserInterface::class);
    });

    it('should return correct username and password', function () {
        $username = 'testuser';
        $password = 'password123';
        $user = new MemoryUser($username, $password);

        expect($user->username())->toBe($username);
        expect($user->getIdentifier())->toBe($username);
        expect($user->getPassword())->toBe($password);
    });

    it('should serialize and unserialize correctly', function () {
        $username = 'testuser';
        $password = 'password123';
        $user = new MemoryUser($username, $password);

        $data = $user->__serialize();
        $newUser = new MemoryUser('', '');
        $newUser->__unserialize($data);

        expect($newUser->username())->toBe($username);
        expect($newUser->getPassword())->toBe($password);
    });
});
