<?php

namespace Gzhegow\Router;

use Gzhegow\Router\Route\Route;
use Gzhegow\Router\Pipeline\Pipeline;
use Gzhegow\Router\Handler\Action\GenericAction;
use Gzhegow\Router\Handler\Fallback\GenericFallback;
use Gzhegow\Router\Handler\Middleware\GenericMiddleware;


interface RouterProcessorInterface
{
    /**
     * @return array{
     *     0?: mixed
     * }
     */
    public function callMiddleware(GenericMiddleware $middleware, Pipeline $pipeline, ?Route $route, $fnNext, $input = null, $context = null) : array;

    /**
     * @return array{
     *     0?: mixed
     * }
     */
    public function callAction(GenericAction $action, Pipeline $pipeline, ?Route $route, $input = null, $context = null) : array;

    /**
     * @return array{
     *     0?: mixed
     * }
     */
    public function callFallback(GenericFallback $fallback, Pipeline $pipeline, ?Route $route, \Throwable $e, $input = null, $context = null) : array;


    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|T $class
     *
     * @return T
     */
    public function newHandlerObject(string $class, array $parameters = []) : object;
}
