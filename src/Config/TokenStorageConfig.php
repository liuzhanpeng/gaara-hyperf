<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Config;

use Lzpeng\HyperfAuthGuard\Constants;

/**
 * Token存储器配置
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class TokenStorageConfig
{
    /**
     * @param string $type
     * @param array $options
     */
    public function __construct(
        private string $type,
        private array $options = []
    ) {}

    /**
     * @param array $config
     * @return self
     */
    public static function from(array|string $config): self
    {
        if (is_string($config)) {
            switch ($config) {
                case 'session':
                    $config = ['session' => ['prefix' => sprintf('%s:%s:', Constants::__PREFIX, 'session_token')]];
                    break;
                case 'null':
                    $config = ['null' => []];
                    break;
                default:
                    $config = ['custom' => ['class' => $config]];
            }
        }

        if (count($config) !== 1) {
            throw new \InvalidArgumentException('token_storage config must be an associative array with a single key-value pair');
        }

        $type = array_key_first($config);
        $options = $config[$type];

        return new self($type, $options);
    }

    /**
     * 返回类型
     *
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * 返回参数
     * 
     * @return array
     */
    public function options(): array
    {
        return $this->options;
    }
}
