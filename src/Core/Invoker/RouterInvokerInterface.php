<?php

namespace Gzhegow\Router\Core\Invoker;

use Gzhegow\Lib\Modules\Func\GenericCallable;


interface RouterInvokerInterface
{
    /**
     * @template T
     *
     * @param class-string<T> $className
     *
     * @return T
     */
    public function newInvokeObject(string $className, array $args = [], array $options = []) : object;


    /**
     * @param callable|GenericCallable $fn
     */
    public function callUserFunc($fn, ...$args);

    /**
     * @param callable|GenericCallable $fn
     */
    public function callUserFuncArray($fn, array $args = []);
}
