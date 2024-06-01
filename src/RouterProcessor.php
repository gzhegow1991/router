<?php

namespace Gzhegow\Router;

use Gzhegow\Router\Route\Route;
use Gzhegow\Router\Pipeline\Pipeline;
use Gzhegow\Router\Handler\GenericHandler;
use Gzhegow\Router\Exception\RuntimeException;
use Gzhegow\Router\Handler\Action\GenericAction;
use Gzhegow\Router\Handler\Fallback\GenericFallback;
use Gzhegow\Router\Handler\Middleware\GenericMiddleware;


class RouterProcessor implements RouterProcessorInterface
{
    /**
     * @var RouterFactoryInterface
     */
    protected $factory;


    public function __construct(RouterFactoryInterface $factory)
    {
        $this->factory = $factory;
    }


    /**
     * @return array{
     *     0?: mixed
     * }
     */
    public function callMiddleware(
        GenericMiddleware $middleware,
        Pipeline $pipeline, ?Route $route,
        $fnNext, $input = null, $context = null
    ) : array
    {
        $callable = $this->extractHandlerCallable($middleware);

        $result = $this->callUserFuncArray(
            $callable,
            [
                $fnNext,
                $input,
                $context,
                //
                'middleware' => $middleware,
                'pipeline'   => $pipeline,
                'route'      => $route,
            ]
        );

        return (null !== $result)
            ? [ $result ]
            : [];
    }

    /**
     * @return array{
     *     0?: mixed
     * }
     */
    public function callAction(
        GenericAction $action,
        Pipeline $pipeline, ?Route $route,
        $input = null, $context = null
    ) : array
    {
        $callable = $this->extractHandlerCallable($action);

        $result = $this->callUserFuncArray(
            $callable,
            [
                $input,
                $context,
                //
                'action'   => $action,
                'pipeline' => $pipeline,
                'route'    => $route,
            ]
        );

        return (null !== $result)
            ? [ $result ]
            : [];
    }

    /**
     * @return array{
     *     0?: mixed
     * }
     */
    public function callFallback(
        GenericFallback $fallback,
        Pipeline $pipeline, ?Route $route,
        \Throwable $e, $input = null, $context = null
    ) : array
    {
        $callable = $this->extractHandlerCallable($fallback);

        $result = $this->callUserFuncArray(
            $callable,
            [
                $e,
                $input,
                $context,
                //
                'fallback' => $fallback,
                'pipeline' => $pipeline,
                'route'    => $route,
            ]
        );

        return (null !== $result)
            ? [ $result ]
            : [];
    }


    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|T $class
     *
     * @return T
     */
    public function newHandlerObject(string $class, array $parameters = []) : object
    {
        return new $class(...$parameters);
    }


    protected function callUserFunc($fn, ...$args) // : mixed
    {
        $result = call_user_func_array($fn, ...$args);

        return $result;
    }

    protected function callUserFuncArray($fn, array $args) // : mixed
    {
        [ $list ] = Lib::array_kwargs($args);

        $result = call_user_func_array($fn, $list);

        return $result;
    }


    /**
     * @return callable
     */
    protected function extractHandlerCallable(GenericHandler $handler)
    {
        $fn = null;

        if ($handler->closure) {
            $fn = $handler->closure;

        } elseif ($handler->method) {
            $object = $handler->methodObject ?? $this->newHandlerObject($handler->methodClass);
            $method = $handler->methodName;

            $fn = [ $object, $method ];

        } elseif ($handler->invokable) {
            $object = $handler->invokableObject ?? $this->newHandlerObject($handler->invokableClass);

            $fn = $object;

        } elseif ($handler->function) {
            $fn = $handler->function;
        }

        if (! is_callable($fn)) {
            throw new RuntimeException(
                'Unable to extract callable, maybe cache is outdated or method/function was removed? '
                . 'Handler: ' . Lib::php_dump($handler)
            );
        }

        return $fn;
    }
}
