<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Config;

/**
 * 用用户提供者配置
 * 
 * @author lzpeng <liuzhanpeng@gmail.com> 
 */
class UserProviderConfig
{
    /**
     * @param string $id
     * @param array $params
     */
    public function __construct(
        private string $id,
        private array $params
    ) {}

    public static function from(array $config): self
    {
        if (count($config) !== 1) {
            throw new \InvalidArgumentException('UserProvider config must be a single array');
        }

        $id = array_key_first($config);
        $params = $config[$id];

        return new self($id, $params);
    }

    /**
     * 返回类型
     *
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * 返回参数
     *
     * @return array
     */
    public function params(): array
    {
        return $this->params;
    }
}
