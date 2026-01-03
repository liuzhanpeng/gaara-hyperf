<?php

declare(strict_types=1);

namespace GaaraHyperf\Encryptor;

/**
 * 数据加密器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class Encryptor implements EncryptorInterface
{
    /**
     * @param string $key base64编码的密钥
     * @throws \InvalidArgumentException
     */
    public function __construct(private string $key, private string $algo = 'AES-256-CBC')
    {
        $decodedKey = base64_decode($key, true);
        if ($decodedKey === false) {
            throw new \InvalidArgumentException('Invalid base64 key provided');
        }

        $expectedKeyLength = $this->getExpectedKeyLength($this->algo);
        if (strlen($decodedKey) !== $expectedKeyLength) {
            throw new \InvalidArgumentException("Key length must be " . $expectedKeyLength . " bytes");
        }

        $this->key = $decodedKey;
    }

    /**
     * 加密数据
     *
     * @param string $data 要加密的数据
     * @return string 十六进制编码的密文（包含IV）
     * @throws \RuntimeException
     */
    public function encrypt(string $data): string
    {
        $iv = $this->generateRandomIv();

        $encrypted = openssl_encrypt(
            $data,
            $this->algo,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new \RuntimeException('Encryption failed: ' . openssl_error_string());
        }

        return bin2hex($iv . $encrypted);
    }

    /**
     * 解密数据
     *
     * @param string $data 十六进制编码的密文（包含IV）
     * @return string 解密后的明文
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function decrypt(string $data): string
    {
        $binaryData = hex2bin($data);
        if ($binaryData === false) {
            throw new \InvalidArgumentException('Invalid hex data provided');
        }

        $ivSize = openssl_cipher_iv_length($this->algo);
        if (strlen($binaryData) < $ivSize) {
            throw new \InvalidArgumentException('Data too short to contain IV');
        }

        // 从数据中分离IV和密文
        $iv = substr($binaryData, 0, $ivSize);
        $encrypted = substr($binaryData, $ivSize);

        $decrypted = openssl_decrypt(
            $encrypted,
            $this->algo,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            throw new \RuntimeException('Decryption failed: ' . openssl_error_string());
        }

        return $decrypted;
    }

    /**
     * 生成随机IV（初始化向量）
     *
     * @return string
     * @throws \RuntimeException
     */
    private function generateRandomIv(): string
    {
        $size = openssl_cipher_iv_length($this->algo);
        $iv = openssl_random_pseudo_bytes($size, $strong);

        if (!$strong) {
            throw new \RuntimeException('Unable to generate cryptographically strong random IV');
        }

        return $iv;
    }

    /**
     * 获取算法期望的密钥长度
     *
     * @param string $algo
     * @return integer
     */
    private function getExpectedKeyLength(string $algo): int
    {
        return match ($algo) {
            'AES-128-CBC' => 16,
            'AES-192-CBC' => 24,
            'AES-256-CBC' => 32,
            'SM4-CBC' => 16,
            'DES-CBC' => 8,
            '3DES-CBC', 'DES-EDE3-CBC' => 24,
            default => throw new \InvalidArgumentException("Unsupported algorithm: {$algo}")
        };
    }
}
