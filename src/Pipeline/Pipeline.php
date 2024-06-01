<?php

namespace Gzhegow\Router\Pipeline;

use Gzhegow\Router\Route\Route;
use Gzhegow\Router\RouterProcessor;
use Gzhegow\Router\Exception\RuntimeException;
use Gzhegow\Router\Handler\Action\GenericAction;
use Gzhegow\Router\Handler\Fallback\GenericFallback;
use Gzhegow\Router\Handler\Middleware\GenericMiddleware;


class Pipeline
{
    /**
     * @var RouterProcessor
     */
    protected $processor;

    /**
     * @var int
     */
    protected $id = 0;
    /**
     * @var GenericMiddleware[]
     */
    protected $middlewareList = [];
    /**
     * @var GenericAction[]
     */
    protected $actionList = [];

    /**
     * @var int
     */
    protected $fallbackId = 0;
    /**
     * @var GenericFallback[]
     */
    protected $fallbackList = [];

    /**
     * @var int
     */
    protected $step;
    /**
     * @var Route
     */
    protected $route;
    /**
     * @var \Throwable
     */
    protected $throwable;


    public function __construct(RouterProcessor $processor)
    {
        $this->processor = $processor;
    }


    public function addMiddlewares(array $middlewares) // : static
    {
        foreach ( $middlewares as $middleware ) {
            $this->addMiddleware($middleware);
        }

        return $this;
    }

    public function addMiddleware(GenericMiddleware $middleware) // : static
    {
        $this->middlewareList[ $this->id++ ] = $middleware;

        return $this;
    }

    public function addActions(array $actions) // : static
    {
        foreach ( $actions as $action ) {
            $this->addAction($action);
        }

        return $this;
    }

    public function addAction(GenericAction $action) // : static
    {
        $this->actionList[ $this->id++ ] = $action;

        return $this;
    }


    public function addFallbacks(array $fallbacks) // : static
    {
        foreach ( $fallbacks as $fallback ) {
            $this->addFallback($fallback);
        }

        return $this;
    }

    public function addFallback(GenericFallback $fallback) // : static
    {
        $this->fallbackList[ $this->fallbackId++ ] = $fallback;

        return $this;
    }


    /**
     * @throws \Throwable
     */
    public function catch(\Throwable $e, $input = null, $context = null) // : mixed
    {
        $this->throwable = $e;

        $result = $input;

        foreach ( $this->fallbackList as $fallback ) {
            $result = $this->processor->callFallback(
                $fallback, $this->route,
                $e, $result, $context
            );
        }

        if ($result === null) {
            throw $e;
        }

        return $result;
    }


    /**
     * @throws \Throwable
     */
    public function run(Route $route, $input = null, $context = null) // : mixed
    {
        $this->route = $route;

        $this->step = 0;

        try {
            $result = $this->current($input, $context);
        }
        catch ( \Throwable $e ) {
            $this->throwable = $e;

            $result = $this->catch($e, $input, $context);
        }

        return $result;
    }

    public function next($input = null, $context = null) // : mixed
    {
        $this->step++;

        $result = $this->current($input, $context);

        return $result;
    }

    public function current($input = null, $context = null) // : mixed
    {
        $middleware = null;
        $action = null;

        $handler = null
            ?? ($middleware = $this->middlewareList[ $this->step ] ?? null)
            ?? ($action = $this->actionList[ $this->step ] ?? null);

        if (null === $handler) {
            return $input;
        }

        if ($middleware) {
            $result = $this->processor->callMiddleware(
                $middleware, $this->route,
                [ $this, 'next' ], $input, $context
            );

        } else {
            $result = $this->processor->callAction(
                $action, $this->route,
                $input, $context
            );
        }

        return $result;
    }
}
