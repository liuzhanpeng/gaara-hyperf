<?php

declare(strict_types=1);

namespace GaaraHyperf\Encryptor;

/**
 * 数据加密器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface EncryptorInterface
{
    /**
     * 加密数据
     *
     * @param string $data 要加密的数据
     * @return string 十六进制编码的密文（包含IV）
     */
    public function encrypt(string $data): string;

    /**
     * 解密数据
     *
     * @param string $hexData 十六进制编码的密文（包含IV）
     * @return string 解密后的明文数据
     */
    public function decrypt(string $hexData): string;
}
