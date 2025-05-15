<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\UserProvider;

use Lzpeng\HyperfAuthGuard\User\UserInterface;

/**
 * 基于Hyperf内置数据库模型用户提供者
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class ModelUserProvider implements UserProviderInterface
{
    /**
     * @param string $class
     * @param string $identifier
     */
    public function __construct(
        private string $class,
        private string $identifier
    ) {}

    /**
     * @inheritDoc
     *
     * @param string $identifier
     * @return UserInterface|null
     */
    public function findByIdentifier(string $identifier): ?UserInterface
    {
        $model = $this->class::query()->where($this->identifier, $identifier)->first();
        if (!$model) {
            return null;
        }

        if (!$model instanceof UserInterface) {
            throw new \LogicException("{$this->class} must implement UserInterface");
        }

        return $model;
    }
}
