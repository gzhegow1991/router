<?php

namespace Gzhegow\Router\Pipeline;

use Gzhegow\Router\Route\Route;
use Gzhegow\Router\RouterProcessorInterface;
use Gzhegow\Router\Handler\Action\GenericAction;
use Gzhegow\Router\Handler\Fallback\GenericFallback;
use Gzhegow\Router\Handler\Middleware\GenericMiddleware;


class Pipeline
{
    const HANDLER_MIDDLEWARE = 'MIDDLEWARE';
    const HANDLER_ACTION     = 'ACTION';
    const HANDLER_FALLBACK   = 'FALLBACK';

    const LIST_HANDLER = [
        self::HANDLER_MIDDLEWARE => true,
        self::HANDLER_ACTION     => true,
        self::HANDLER_FALLBACK   => true,
    ];


    /**
     * @var RouterProcessorInterface
     */
    protected $processor;

    /**
     * @var int
     */
    protected $id = 0;
    /**
     * @var array<int, string>
     */
    protected $handlerList = [];
    /**
     * @var GenericMiddleware[]
     */
    protected $middlewareList = [];
    /**
     * @var GenericAction[]
     */
    protected $actionList = [];
    /**
     * @var GenericFallback[]
     */
    protected $fallbackList = [];

    /**
     * @var Route
     */
    protected $route;
    /**
     * @var \Throwable
     */
    protected $throwable;


    public function __construct(RouterProcessorInterface $processor)
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
        $id = ++$this->id;

        $this->handlerList[ $id ] = static::HANDLER_MIDDLEWARE;

        $this->middlewareList[ $id ] = $middleware;

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
        $id = ++$this->id;

        $this->handlerList[ $id ] = static::HANDLER_ACTION;

        $this->actionList[ $id ] = $action;

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
        $id = ++$this->id;

        $this->handlerList[ $id ] = static::HANDLER_FALLBACK;

        $this->fallbackList[ $id ] = $fallback;

        return $this;
    }


    /**
     * @throws \Throwable
     */
    public function runRoute(Route $route, $input = null, $context = null) // : mixed
    {
        $this->route = $route;

        $result = $this->run($input, $context);

        return $result;
    }

    /**
     * @throws \Throwable
     */
    public function runThrowable(\Throwable $e, $input = null, $context = null) // : mixed
    {
        $this->throwable = $e;

        $result = $this->run($input, $context);

        return $result;
    }


    /**
     * @throws \Throwable
     */
    public function run($input = null, $context = null) // : mixed
    {
        reset($this->handlerList);

        $result = null;

        while ( null !== key($this->handlerList) ) {
            $array = $this->doCurrent($input, $context);

            if ($array) {
                [ $result ] = $array;
            }

            next($this->handlerList);
        }

        if ($this->throwable) {
            throw $this->throwable;
        }

        return $result;
    }


    public function next($input = null, $context = null) // : mixed
    {
        $result = null;

        $array = $this->doNext($input, $context);

        if ($array) {
            [ $result ] = $array;
        }

        return $result;
    }

    public function current($input = null, $context = null) // : mixed
    {
        $result = null;

        $array = $this->doCurrent($input, $context);

        if ($array) {
            [ $result ] = $array;
        }

        return $result;
    }


    /**
     * @return array{
     *     0?: mixed
     * }
     */
    protected function doNext($input = null, $context = null) : array
    {
        next($this->handlerList);

        $result = $this->doCurrent($input, $context);

        return $result;
    }

    /**
     * @return array{
     *     0?: mixed
     * }
     */
    protected function doCurrent($input = null, $context = null) : array
    {
        $id = key($this->handlerList);

        $middleware = null;
        $action = null;
        $fallback = null;

        $handler = null
            ?? ($middleware = $this->middlewareList[ $id ] ?? null)
            ?? ($action = $this->actionList[ $id ] ?? null)
            ?? ($fallback = $this->fallbackList[ $id ] ?? null);

        $result = [];

        if (null === $handler) {
            return $result;
        }

        if ($this->throwable) {
            if ($fallback) {
                $result = $this->processor->callFallback(
                    $fallback,
                    $this, $this->route,
                    $this->throwable, $input, $context
                );

                if ($result) {
                    $this->throwable = null;
                }
            }

        } else {
            try {
                if ($middleware) {
                    $result = $this->processor->callMiddleware(
                        $middleware,
                        $this, $this->route,
                        [ $this, 'next' ], $input, $context
                    );

                } elseif ($action) {
                    $result = $this->processor->callAction(
                        $action,
                        $this, $this->route,
                        $input, $context
                    );
                }
            }
            catch ( \Throwable $e ) {
                $this->throwable = $e;

                do {
                    next($this->handlerList);

                    $result = $this->doCurrent($input, $context);
                } while ( ! $result && (null !== key($this->handlerList)) );
            }
        }

        return $result;
    }
}
