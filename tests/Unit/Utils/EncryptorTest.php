<?php

declare(strict_types=1);

namespace Tests\Unit\Util;

use Lzpeng\HyperfAuthGuard\Util\Encryptor;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use RuntimeException;

/**
 * Encryptor 单元测试
 */
class EncryptorTest extends TestCase
{
    private string $base64Key256;
    private string $base64Key128;
    private string $base64Key192;

    protected function setUp(): void
    {
        // 生成测试用的 base64 编码密钥
        $this->base64Key256 = base64_encode(random_bytes(32)); // AES-256-CBC
        $this->base64Key128 = base64_encode(random_bytes(16)); // AES-128-CBC
        $this->base64Key192 = base64_encode(random_bytes(24)); // AES-192-CBC
    }

    /**
     * 测试构造函数正常情况
     */
    public function test_constructor_with_valid_key(): void
    {
        $encryptor = new Encryptor($this->base64Key256);
        $this->assertInstanceOf(Encryptor::class, $encryptor);
    }

    /**
     * 测试构造函数指定算法
     */
    public function test_constructor_with_different_algorithms(): void
    {
        // AES-128-CBC
        $encryptor128 = new Encryptor($this->base64Key128, 'AES-128-CBC');
        $this->assertInstanceOf(Encryptor::class, $encryptor128);

        // AES-192-CBC
        $encryptor192 = new Encryptor($this->base64Key192, 'AES-192-CBC');
        $this->assertInstanceOf(Encryptor::class, $encryptor192);
    }

    /**
     * 测试无效的 base64 密钥
     */
    public function test_constructor_with_invalid_base64_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64 key provided');

        new Encryptor('invalid-base64-key!!!');
    }

    /**
     * 测试密钥长度不匹配
     */
    public function test_constructor_with_wrong_key_length(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Key length must be 32 bytes');

        // 使用 16 字节密钥但指定 AES-256-CBC（需要 32 字节）
        new Encryptor($this->base64Key128, 'AES-256-CBC');
    }

    /**
     * 测试不支持的算法
     */
    public function test_constructor_with_unsupported_algorithm(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported algorithm: UNSUPPORTED-ALGO');

        new Encryptor($this->base64Key256, 'UNSUPPORTED-ALGO');
    }

    /**
     * 测试基本的加密和解密
     */
    public function test_encrypt_and_decrypt_basic(): void
    {
        $encryptor = new Encryptor($this->base64Key256);
        $plaintext = 'Hello, World!';

        $encrypted = $encryptor->encrypt($plaintext);
        $decrypted = $encryptor->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted);
    }

    /**
     * 测试空字符串的加密和解密
     */
    public function test_encrypt_and_decrypt_empty_string(): void
    {
        $encryptor = new Encryptor($this->base64Key256);
        $plaintext = '';

        $encrypted = $encryptor->encrypt($plaintext);
        $decrypted = $encryptor->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted);
    }

    /**
     * 测试长文本的加密和解密
     */
    public function test_encrypt_and_decrypt_long_text(): void
    {
        $encryptor = new Encryptor($this->base64Key256);
        $plaintext = str_repeat('This is a long text message. ', 100);

        $encrypted = $encryptor->encrypt($plaintext);
        $decrypted = $encryptor->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted);
    }

    /**
     * 测试包含特殊字符的文本
     */
    public function test_encrypt_and_decrypt_special_characters(): void
    {
        $encryptor = new Encryptor($this->base64Key256);
        $plaintext = "特殊字符测试 🚀 ñáéíóú @#$%^&*()";

        $encrypted = $encryptor->encrypt($plaintext);
        $decrypted = $encryptor->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted);
    }

    /**
     * 测试二进制数据的加密和解密
     */
    public function test_encrypt_and_decrypt_binary_data(): void
    {
        $encryptor = new Encryptor($this->base64Key256);
        $binaryData = random_bytes(256);

        $encrypted = $encryptor->encrypt($binaryData);
        $decrypted = $encryptor->decrypt($encrypted);

        $this->assertEquals($binaryData, $decrypted);
    }

    /**
     * 测试加密结果格式
     */
    public function test_encrypt_returns_hex_string(): void
    {
        $encryptor = new Encryptor($this->base64Key256);
        $encrypted = $encryptor->encrypt('test');

        // 检查是否为有效的十六进制字符串
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/i', $encrypted);

        // 检查长度（应该包含 IV + 密文）
        $ivSize = openssl_cipher_iv_length('AES-256-CBC');
        $expectedMinLength = $ivSize * 2; // IV 的十六进制长度
        $this->assertGreaterThanOrEqual($expectedMinLength, strlen($encrypted));
    }

    /**
     * 测试相同明文多次加密结果不同（因为 IV 随机）
     */
    public function test_encrypt_same_plaintext_produces_different_ciphertext(): void
    {
        $encryptor = new Encryptor($this->base64Key256);
        $plaintext = 'test message';

        $encrypted1 = $encryptor->encrypt($plaintext);
        $encrypted2 = $encryptor->encrypt($plaintext);

        $this->assertNotEquals($encrypted1, $encrypted2);

        // 但解密结果应该相同
        $this->assertEquals($plaintext, $encryptor->decrypt($encrypted1));
        $this->assertEquals($plaintext, $encryptor->decrypt($encrypted2));
    }

    /**
     * 测试解密无效的十六进制数据
     */
    public function test_decrypt_invalid_hex_data(): void
    {
        $encryptor = new Encryptor($this->base64Key256);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid hex data provided');

        // 使用包含非十六进制字符的字符串
        $encryptor->decrypt('zzzzzzzz');
    }

    /**
     * 测试解密数据太短（没有足够的 IV）
     */
    public function test_decrypt_data_too_short(): void
    {
        $encryptor = new Encryptor($this->base64Key256);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Data too short to contain IV');

        // 只提供很短的十六进制数据
        $encryptor->decrypt('abcd');
    }

    /**
     * 测试解密被篡改的数据
     */
    public function test_decrypt_tampered_data(): void
    {
        $encryptor = new Encryptor($this->base64Key256);
        $encrypted = $encryptor->encrypt('test');

        // 修改最后两个字符（保持偶数长度的十六进制）
        $tampered = substr($encrypted, 0, -2) . 'ff';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');

        $encryptor->decrypt($tampered);
    }

    /**
     * 测试不同算法的加密解密
     */
    public function test_different_algorithms(): void
    {
        $testData = 'Test message for different algorithms';

        $algorithms = [
            'AES-128-CBC' => $this->base64Key128,
            'AES-192-CBC' => $this->base64Key192,
            'AES-256-CBC' => $this->base64Key256,
        ];

        foreach ($algorithms as $algo => $key) {
            $encryptor = new Encryptor($key, $algo);
            $encrypted = $encryptor->encrypt($testData);
            $decrypted = $encryptor->decrypt($encrypted);

            $this->assertEquals($testData, $decrypted, "Failed for algorithm: {$algo}");
        }
    }

    /**
     * 测试密钥隔离（不同密钥无法解密）
     */
    public function test_key_isolation(): void
    {
        $key1 = base64_encode(random_bytes(32));
        $key2 = base64_encode(random_bytes(32));

        $encryptor1 = new Encryptor($key1);
        $encryptor2 = new Encryptor($key2);

        $plaintext = 'secret message';
        $encrypted = $encryptor1->encrypt($plaintext);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');

        // 使用不同密钥尝试解密应该失败
        $encryptor2->decrypt($encrypted);
    }

    /**
     * 测试大数据处理
     */
    public function test_encrypt_large_data(): void
    {
        $encryptor = new Encryptor($this->base64Key256);

        // 生成 1MB 的测试数据
        $largeData = str_repeat('A', 1024 * 1024);

        $encrypted = $encryptor->encrypt($largeData);
        $decrypted = $encryptor->decrypt($encrypted);

        $this->assertEquals($largeData, $decrypted);
    }

    /**
     * 测试 JSON 数据的加密解密
     */
    public function test_encrypt_json_data(): void
    {
        $encryptor = new Encryptor($this->base64Key256);

        $data = [
            'user_id' => 123,
            'username' => 'testuser',
            'email' => 'test@example.com',
            'metadata' => [
                'created_at' => '2023-01-01',
                'last_login' => null,
            ]
        ];

        $jsonData = json_encode($data);
        $encrypted = $encryptor->encrypt($jsonData);
        $decrypted = $encryptor->decrypt($encrypted);

        $this->assertEquals($jsonData, $decrypted);
        $this->assertEquals($data, json_decode($decrypted, true));
    }

    /**
     * 测试支持的算法列表
     */
    public function test_supported_algorithms(): void
    {
        $supportedAlgorithms = [
            'AES-128-CBC' => 16,
            'AES-192-CBC' => 24,
            'AES-256-CBC' => 32,
        ];

        foreach ($supportedAlgorithms as $algo => $keyLength) {
            $key = base64_encode(random_bytes($keyLength));
            $encryptor = new Encryptor($key, $algo);

            $testData = "Test data for {$algo}";
            $encrypted = $encryptor->encrypt($testData);
            $decrypted = $encryptor->decrypt($encrypted);

            $this->assertEquals($testData, $decrypted, "Algorithm {$algo} failed");
        }
    }

    /**
     * 测试 IV 长度正确性
     */
    public function test_iv_length_correctness(): void
    {
        $encryptor = new Encryptor($this->base64Key256);
        $encrypted = $encryptor->encrypt('test');

        // 从加密数据中提取 IV
        $binaryData = hex2bin($encrypted);
        $ivSize = openssl_cipher_iv_length('AES-256-CBC');
        $extractedIv = substr($binaryData, 0, $ivSize);

        $this->assertEquals($ivSize, strlen($extractedIv));
    }

    /**
     * 性能基准测试
     */
    public function test_performance_benchmark(): void
    {
        $encryptor = new Encryptor($this->base64Key256);
        $testData = 'Performance test data';

        $startTime = microtime(true);

        // 执行 1000 次加密和解密
        for ($i = 0; $i < 1000; $i++) {
            $encrypted = $encryptor->encrypt($testData);
            $decrypted = $encryptor->decrypt($encrypted);
            $this->assertEquals($testData, $decrypted);
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // 性能断言：1000次操作应该在合理时间内完成（比如 10 秒）
        $this->assertLessThan(10.0, $duration, 'Performance test failed: took too long');

        // 输出性能信息（仅用于调试）
        echo "\nPerformance: 1000 encrypt/decrypt operations took {$duration} seconds\n";
    }

    /**
     * 测试密钥强度要求
     */
    public function test_key_strength_requirements(): void
    {
        // 测试弱密钥（全零）
        $weakKey = base64_encode(str_repeat("\0", 32));
        $encryptor = new Encryptor($weakKey);

        // 即使是弱密钥，加密解密也应该正常工作
        $plaintext = 'test with weak key';
        $encrypted = $encryptor->encrypt($plaintext);
        $decrypted = $encryptor->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted);
    }

    /**
     * 测试边界情况：最大支持的数据大小
     */
    public function test_maximum_data_size(): void
    {
        $encryptor = new Encryptor($this->base64Key256);

        // 测试 10MB 数据（接近实际使用上限）
        $largeData = str_repeat('X', 10 * 1024 * 1024);

        $encrypted = $encryptor->encrypt($largeData);
        $decrypted = $encryptor->decrypt($encrypted);

        $this->assertEquals($largeData, $decrypted);
        $this->assertEquals(strlen($largeData), strlen($decrypted));
    }

    /**
     * 测试奇数长度的十六进制字符串解密
     */
    public function test_decrypt_odd_length_hex_string(): void
    {
        $encryptor = new Encryptor($this->base64Key256);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid hex data provided');

        // 奇数长度的十六进制字符串
        $encryptor->decrypt('abc123d');
    }

    /**
     * 测试多种密钥强度
     */
    public function test_various_key_strengths(): void
    {
        // 测试不同的密钥模式
        $keyPatterns = [
            str_repeat("\x00", 32), // 全零密钥
            str_repeat("\xFF", 32), // 全1密钥
            random_bytes(32),       // 随机密钥
        ];

        foreach ($keyPatterns as $index => $rawKey) {
            $base64Key = base64_encode($rawKey);
            $encryptor = new Encryptor($base64Key);

            $testData = "Test with key pattern {$index}";
            $encrypted = $encryptor->encrypt($testData);
            $decrypted = $encryptor->decrypt($encrypted);

            $this->assertEquals($testData, $decrypted, "Failed with key pattern {$index}");
        }
    }
}
