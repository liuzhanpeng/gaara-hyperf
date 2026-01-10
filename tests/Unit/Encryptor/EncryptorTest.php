<?php

declare(strict_types=1);

describe('Encryptor', function () {
    beforeEach(function () {
        $this->encryptor = new GaaraHyperf\Encryptor\Encryptor(
            'my_secret_key_123456789012345678', // 32 bytes key for AES-256
            'aes-256-cbc'
        );
    });

    it('should encrypt and decrypt data correctly', function () {
        $data = 'Hello, World!';
        $encrypted = $this->encryptor->encrypt($data);
        expect($encrypted)->not->toBe($data);

        $decrypted = $this->encryptor->decrypt($encrypted);
        expect($decrypted)->toBe($data);
    });

    it('should throw exception for invalid key length', function () {
        expect(function () {
            new GaaraHyperf\Encryptor\Encryptor('short_key');
        })->toThrow(\InvalidArgumentException::class);
    });

    it('should throw exception for invalid encrypted data', function () {
        expect(function () {
            $this->encryptor->decrypt(bin2hex('invalid_encrypted_data'));
        })->toThrow(\RuntimeException::class);
    });
});
