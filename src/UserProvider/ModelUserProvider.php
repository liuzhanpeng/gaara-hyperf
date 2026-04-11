<?php

declare(strict_types=1);

namespace GaaraHyperf\UserProvider;

use GaaraHyperf\User\UserInterface;
use Hyperf\DbConnection\Model\Model;
use LogicException;

/**
 * 基于Hyperf内置数据库模型用户提供者.
 */
class ModelUserProvider implements UserProviderInterface
{
    public function __construct(
        private string $class,
        private string $identifier
    ) {
        if (empty($this->class) || ! class_exists($this->class)) {
            throw new LogicException("The model class '{$this->class}' does not exist.");
        }

        if (! is_subclass_of($this->class, Model::class)) {
            throw new LogicException("The model class '{$this->class}' must extend " . Model::class);
        }

        if (empty($this->identifier)) {
            throw new LogicException('The identifier field name cannot be empty.');
        }
    }

    public function findByIdentifier(string $identifier): ?UserInterface
    {
        $model = $this->class::query()->where($this->identifier, $identifier)->first();
        if (! $model) {
            return null;
        }

        if (! $model instanceof UserInterface) {
            throw new LogicException("{$this->class} must implement UserInterface");
        }

        return $model;
    }
}
