<?php

namespace Gzhegow\Router\Core\Dispatcher;

use Gzhegow\Lib\Lib;
use Gzhegow\Router\Router;
use Gzhegow\Router\RouterInterface;
use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Core\Node\RouterNode;
use Gzhegow\Router\Core\Config\RouterConfig;
use Gzhegow\Router\Exception\LogicException;
use Gzhegow\Lib\Modules\Func\Pipe\PipeContext;
use Gzhegow\Router\Exception\Runtime\NotFoundException;
use Gzhegow\Router\Core\Invoker\RouterInvokerInterface;
use Gzhegow\Router\Exception\Exception\DispatchException;
use Gzhegow\Router\Core\Collection\RouterRouteCollection;
use Gzhegow\Router\Core\Collection\RouterFallbackCollection;
use Gzhegow\Router\Core\Collection\RouterMiddlewareCollection;
use Gzhegow\Router\Core\Handler\Fallback\GenericHandlerFallback;
use Gzhegow\Router\Core\Handler\Middleware\GenericHandlerMiddleware;


class RouterDispatcher implements RouterDispatcherInterface
{
    /**
     * @var RouterConfig
     */
    protected $routerConfig;

    /**
     * @var RouterInvokerInterface
     */
    protected $routerInvoker;

    /**
     * @var RouterRouteCollection
     */
    protected $routeCollection;
    /**
     * @var RouterMiddlewareCollection
     */
    protected $middlewareCollection;
    /**
     * @var RouterFallbackCollection
     */
    protected $fallbackCollection;

    /**
     * @var RouterNode
     */
    protected $rootRouterNode;

    /**
     * @var RouterDispatcherContract
     */
    protected $dispatchContract;
    /**
     * @var string
     */
    protected $dispatchRequestMethod;
    /**
     * @var string
     */
    protected $dispatchRequestUri;

    /**
     * @var Route
     */
    protected $dispatchRoute;
    /**
     * @var array
     */
    protected $dispatchActionAttributes = [];


    public function initialize(RouterInterface $router) : void
    {
        $this->routerConfig = $router->getConfig();

        $this->routerInvoker = $router->getRouterInvoker();

        $this->routeCollection = $router->getRouteCollection();
        $this->middlewareCollection = $router->getMiddlewareCollection();
        $this->fallbackCollection = $router->getFallbackCollection();

        $this->rootRouterNode = $router->getRootRouterNode();
    }


    /**
     * @return mixed
     * @throws DispatchException
     */
    public function dispatch(
        RouterDispatcherContract $contract,
        $input = null,
        &$context = null,
        array $args = []
    )
    {
        /**
         * @var GenericHandlerMiddleware[] $dispatchMiddlewareList
         * @var GenericHandlerFallback[]   $dispatchFallbackList
         */

        $theFunc = Lib::func();

        $pipeContext = null;
        if (null !== $context) {
            if ($context instanceof PipeContext) {
                $pipeContext = $context;

            } elseif (is_array($context)) {
                $pipeContext = new PipeContext($context);

            } else {
                throw new LogicException(
                    [ 'The `context` should be an array or an instance of: ' . PipeContext::class, $context ]
                );
            }
        }

        $routerConfig = $this->routerConfig;

        $routeCollection = $this->routeCollection;
        $middlewareCollection = $this->middlewareCollection;
        $fallbackCollection = $this->fallbackCollection;

        $contractRequestMethod = $contract->requestMethod->getValue();
        $contractRequestUri = $contract->requestUri->getValue();

        $dispatchRequestMethod = $contractRequestMethod;
        if ($routerConfig->dispatchForceMethod) {
            $dispatchRequestMethod = $routerConfig->dispatchForceMethod;
        }

        $dispatchRequestUri = $contractRequestUri;
        if ($routerConfig->dispatchTrailingSlashMode) {
            $dispatchRequestUri = rtrim($dispatchRequestUri, '/');

            if ($routerConfig->dispatchTrailingSlashMode === Router::TRAILING_SLASH_ALWAYS) {
                $dispatchRequestUri = $dispatchRequestUri . '/';
            }
        }

        $this->dispatchContract = $contract;
        $this->dispatchRequestMethod = $dispatchRequestMethod;
        $this->dispatchRequestUri = $dispatchRequestUri;

        $this->dispatchRoute = null;
        $this->dispatchActionAttributes = [];

        $dispatchRouteIndex = [];

        $dispatchActionAttributes = [];
        $dispatchMiddlewareList = [];
        $dispatchFallbackList = [];

        $routeNodeCurrent = $this->rootRouterNode;

        $routePathCurrent = [ '' ];
        $routeSubpathList = [ '/' ];

        $slice = $dispatchRequestUri;
        $slice = trim($slice, '/');
        $slice = explode('/', $slice);
        while ( [] !== $slice ) {
            $requestUriPart = array_shift($slice);

            $isLast = ([] === $slice);

            if ($isLast) {
                if (isset($routeNodeCurrent->routeIndexByPart[ $requestUriPart ])) {
                    $dispatchRouteIndex = $routeNodeCurrent->routeIndexByPart[ $requestUriPart ];

                    break;
                }

                foreach ( $routeNodeCurrent->routeIndexByRegex as $regex => $routeIndex ) {
                    if (preg_match('/^' . $regex . '$/', $requestUriPart, $matches)) {
                        $dispatchRouteIndex = $routeIndex;

                        foreach ( $matches as $key => $value ) {
                            if (is_string($key)) {
                                $dispatchActionAttributes[ $key ] = $value;
                            }
                        }

                        break 2;
                    }
                }

                $routePathCurrent[] = $requestUriPart;
                $routeSubpathList[] = implode('/', $routePathCurrent);

            } else {
                if (isset($routeNodeCurrent->childrenByPart[ $requestUriPart ])) {
                    $routeNodeCurrent = $routeNodeCurrent->childrenByPart[ $requestUriPart ];

                    $routePathCurrent[] = $routeNodeCurrent->part;
                    $routeSubpathList[] = implode('/', $routePathCurrent);

                    continue;
                }

                foreach ( $routeNodeCurrent->childrenByRegex as $regex => $routeNode ) {
                    if (preg_match('/^' . $regex . '$/', $requestUriPart, $matches)) {
                        $routeNodeCurrent = $routeNode;

                        foreach ( $matches as $key => $value ) {
                            if (is_string($key)) {
                                $dispatchActionAttributes[ $key ] = $value;
                            }
                        }

                        $routePathCurrent[] = $routeNodeCurrent->part;
                        $routeSubpathList[] = implode('/', $routePathCurrent);

                        continue 2;
                    }
                }
            }
        }

        $middlewareIndexes = [
            'path' => [],
            'tags' => [],
        ];
        $fallbackIndexes = [
            'path' => [],
            'tags' => [],
        ];
        foreach ( $routeSubpathList as $routeSubpath ) {
            if (isset($middlewareCollection->middlewareIndexByPath[ $routeSubpath ])) {
                $middlewareIndexes[ 'path' ][ $routeSubpath ] = $middlewareCollection->middlewareIndexByPath[ $routeSubpath ];
            }

            if (isset($fallbackCollection->fallbackIndexByPath[ $routeSubpath ])) {
                $fallbackIndexes[ 'path' ][ $routeSubpath ] = $fallbackCollection->fallbackIndexByPath[ $routeSubpath ];
            }
        }

        $dispatchRouteId = null;
        if ([] !== $dispatchRouteIndex) {
            $intersect = [];

            $intersect[] = $dispatchRouteIndex;

            if (! $routerConfig->dispatchIgnoreMethod) {
                $intersect[] = $routeNodeCurrent->routeIndexByMethod[ $dispatchRequestMethod ] ?? [];
            }

            $indexMatch = array_intersect_key(...$intersect);

            if ($indexMatch) {
                $dispatchRouteId = key($indexMatch);
            }
        }

        $dispatchRouteClone = null;
        if (null !== $dispatchRouteId) {
            $dispatchRouteClone = clone $routeCollection->routeList[ $dispatchRouteId ];
        }

        if (null !== $dispatchRouteClone) {
            $routePath = $dispatchRouteClone->path;

            if (isset($middlewareCollection->middlewareIndexByPath[ $routePath ])) {
                $middlewareIndexes[ 'path' ][ $routePath ] = $middlewareCollection->middlewareIndexByPath[ $routePath ];
            }

            if (isset($fallbackCollection->fallbackIndexByPath[ $routePath ])) {
                $fallbackIndexes[ 'path' ][ $routePath ] = $fallbackCollection->fallbackIndexByPath[ $routePath ];
            }

            foreach ( $dispatchRouteClone->tagIndex as $tag => $bool ) {
                if (isset($middlewareCollection->middlewareIndexByTag[ $tag ])) {
                    $middlewareIndexes[ 'tags' ][ $tag ] = $middlewareCollection->middlewareIndexByTag[ $tag ];
                }

                if (isset($fallbackCollection->fallbackIndexByTag[ $tag ])) {
                    $fallbackIndexes[ 'tags' ][ $tag ] = $fallbackCollection->fallbackIndexByTag[ $tag ];
                }
            }
        }

        $fnSort = static function ($a, $b) {
            return strlen($b) <=> strlen($a);
        };

        uksort($middlewareIndexes[ 'path' ], $fnSort);
        uksort($fallbackIndexes[ 'path' ], $fnSort);

        $middlewareIndex = [];
        foreach ( $middlewareIndexes[ 'path' ] as $index ) {
            $middlewareIndex += $index;
        }
        foreach ( $middlewareIndexes[ 'tags' ] as $index ) {
            $middlewareIndex += $index;
        }

        $fallbackIndex = [];
        foreach ( $fallbackIndexes[ 'path' ] as $index ) {
            $fallbackIndex += $index;
        }
        foreach ( $fallbackIndexes[ 'tags' ] as $index ) {
            $fallbackIndex += $index;
        }

        foreach ( $middlewareIndex as $i => $bool ) {
            $dispatchMiddlewareList[ $i ] = $middlewareCollection->middlewareList[ $i ];
        }

        foreach ( $fallbackIndex as $i => $bool ) {
            $dispatchFallbackList[ $i ] = $fallbackCollection->fallbackList[ $i ];
        }

        $pipelineFnCallUserFuncArray = $this->fnPipelineCallGenericHandler();

        $pipeline = $theFunc->newPipe();
        $pipeline
            ->setContext($pipeContext)
            ->setFnCallUserFuncArray($pipelineFnCallUserFuncArray)
        ;

        $pipelineChild = $pipeline;
        foreach ( $dispatchMiddlewareList as $middleware ) {
            $pipelineChild = $pipelineChild->middleware($middleware);
        }

        if ($dispatchRouteClone) {
            $this->dispatchRoute = $dispatchRouteClone;
            $this->dispatchActionAttributes = $dispatchActionAttributes;

            $dispatchRouteClone->dispatchContract = $contract;
            $dispatchRouteClone->dispatchRequestMethod = $dispatchRequestMethod;
            $dispatchRouteClone->dispatchRequestUri = $dispatchRequestUri;

            $dispatchRouteClone->dispatchActionAttributes = $dispatchActionAttributes;

            $dispatchRouteClone->dispatchMiddlewareIndex = [];
            foreach ( $dispatchMiddlewareList as $middleware ) {
                $dispatchRouteClone->dispatchMiddlewareIndex[ $middleware->getKey() ] = true;
            }

            $dispatchRouteClone->dispatchFallbackIndex = [];
            foreach ( $dispatchFallbackList as $fallback ) {
                $dispatchRouteClone->dispatchFallbackIndex[ $fallback->getKey() ] = true;
            }

            $pipelineChild->map($dispatchRouteClone->action);

        } else {
            $throwable = new NotFoundException(
                ''
                . 'Route not found: '
                . '[ ' . $contractRequestUri . ' ]'
                . '[ ' . $dispatchRequestMethod . ' ]'
            );

            $pipelineChild->setThrowable($throwable);
        }

        foreach ( $dispatchMiddlewareList as $devnull ) {
            $pipelineChild = $pipelineChild->endMiddleware();
        }

        foreach ( $dispatchFallbackList as $fallback ) {
            $pipeline->catch($fallback);
        }

        $pipelineArgs = array_merge(
            [ $dispatchRouteClone ],
            $args
        );

        try {
            $result = $pipeline->run(
                $input, $pipelineArgs
            );
        }
        catch ( \Throwable $e ) {
            throw new DispatchException(
                [ 'Unhandled exception occured during dispatch', $e ], $e
            );
        }

        return $result;
    }


    public function getDispatchContract() : RouterDispatcherContract
    {
        return $this->dispatchContract;
    }

    public function getDispatchRequestMethod() : string
    {
        return $this->dispatchRequestMethod;
    }

    public function getDispatchRequestUri() : string
    {
        return $this->dispatchRequestUri;
    }


    public function getDispatchRoute() : Route
    {
        return $this->dispatchRoute;
    }

    public function getDispatchActionAttributes() : array
    {
        return $this->dispatchActionAttributes;
    }


    public function fnPipelineCallGenericHandler()
    {
        $invoker = $this->routerInvoker;

        return static function ($fn, $args) use ($invoker) {
            array_unshift($args, $invoker);

            return call_user_func_array($fn, $args);
        };
    }
}
