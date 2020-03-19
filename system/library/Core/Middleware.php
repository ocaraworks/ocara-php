<?php

namespace Ocara\Core;

use Ocara\Interfaces\Middleware as MiddlewareInterface;

class Middleware extends ServiceProvider implements MiddlewareInterface
{
    /**
     * 处理
     * @param mixed $args
     * @return mixed
     */
    public function handle($args = null)
    {
    }
}