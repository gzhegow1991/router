<?php

namespace Gzhegow\Router;

use Gzhegow\Router\Route\Route;
use Gzhegow\Router\Handler\GenericHandler;
use Gzhegow\Router\Handler\Action\GenericAction;
use Gzhegow\Router\Handler\Fallback\GenericFallback;
use Gzhegow\Router\Handler\Middleware\GenericMiddleware;


class RouterProcessor
{
    /**
     * @var RouterFactory
     */
    protected $factory;


    public function __construct(RouterFactory $factory)
    {
        $this->factory = $factory;
    }


    public function callMiddleware(
        GenericMiddleware $middleware,
        ?Route $route,
        $fnNext, $input = null, $context = null
    ) // : mixed
    {
        $callable = $this->extractCallableFromHandler($middleware);

        $result = $this->callUserFuncArray(
            $callable,
            [
                $fnNext,
                $input,
                $context,
                //
                'middleware' => $middleware,
                'route'      => $route,
            ]
        );

        return $result;
    }

    public function callAction(
        GenericAction $action,
        ?Route $route,
        $input = null, $context = null
    ) // : mixed
    {
        $callable = $this->extractCallableFromHandler($action);

        $result = $this->callUserFuncArray(
            $callable,
            [
                $input,
                $context,
                //
                'action' => $action,
                'route'  => $route,
            ]
        );

        return $result;
    }

    public function callFallback(
        GenericFallback $fallback,
        ?Route $route,
        \Throwable $e, $input = null, $context = null
    )
    {
        $callable = $this->extractCallableFromHandler($fallback);

        $result = $this->callUserFuncArray(
            $callable,
            [
                $e,
                $input,
                $context,
                //
                'fallback' => $fallback,
                'route'    => $route,
            ]
        );

        return $result;
    }

    protected function extractCallableFromHandler(GenericHandler $handler) : callable
    {
        $callable = null;

        if ($handler->closure) {
            $callable = $handler->closure;

        } elseif ($handler->method) {
            $object = $handler->methodObject ?? $this->factory->newHandlerObject($handler->methodClass);
            $method = $handler->methodName;

            $callable = [ $object, $method ];

        } elseif ($handler->invokable) {
            $object = $handler->invokableObject ?? $this->factory->newHandlerObject($handler->invokableClass);

            $callable = $object;

        } elseif ($handler->function) {
            $callable = $handler->function;
        }

        return $callable;
    }


    public function callUserFunc($fn, ...$args)
    {
        $result = call_user_func_array($fn, ...$args);

        return $result;
    }

    public function callUserFuncArray($fn, array $args)
    {
        [ $list ] = _array_kwargs($args);

        $result = call_user_func_array($fn, $list);

        return $result;
    }
}
