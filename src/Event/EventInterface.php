<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Event;

/**
 * 内部事件接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface EventInterface
{
    /**
     * 返回认证守卫名称
     * 
     * @return string
     */
    public function getGuardName(): string;
}
