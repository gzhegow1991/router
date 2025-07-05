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
    protected $dispatchRequestPath;
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
     * @param mixed|RouterDispatcherContract $contract
     * @param array{ 0: array }|PipeContext  $context
     *
     * @return mixed
     * @throws DispatchException
     */
    public function dispatch(
        $contract,
        $input = null,
        $context = null,
        array $args = []
    )
    {
        /**
         * @var GenericHandlerMiddleware[] $dispatchMiddlewareList
         * @var GenericHandlerFallback[]   $dispatchFallbackList
         */

        $theFunc = Lib::func();

        $dispatchContract = RouterDispatcherContract::from($contract);

        $pipeContext = null;
        if (null !== $context) {
            if ($context instanceof PipeContext) {
                $pipeContext = $context;

            } elseif (true
                && is_array($context)
                && isset($context[ 0 ])
                && is_array($context[ 0 ])
            ) {
                $pipeContext = new PipeContext($context[ 0 ]);

            } else {
                throw new LogicException(
                    [ 'The `context` should be an array like `[ &$context ]` or an instance of: ' . PipeContext::class, $context ]
                );
            }
        }

        $routerConfig = $this->routerConfig;

        $routeCollection = $this->routeCollection;
        $middlewareCollection = $this->middlewareCollection;
        $fallbackCollection = $this->fallbackCollection;

        $contractRequestMethod = $dispatchContract->getRequestMethod();

        $contractRequestHttpPath = $dispatchContract->getRequestHttpPath();
        $contractRequestUri = $dispatchContract->getRequestUri();
        $contractRequestPath = $dispatchContract->getRequestPath();

        $dispatchRequestMethod = $contractRequestMethod;
        if ($routerConfig->dispatchForceMethod) {
            $dispatchRequestMethod = $routerConfig->dispatchForceMethod;
        }

        $dispatchRequestUri = $contractRequestUri;
        $dispatchRequestPath = $contractRequestPath;
        if ($routerConfig->dispatchTrailingSlashMode) {
            $dispatchRequestPath = rtrim($dispatchRequestPath, '/');

            if ($routerConfig->dispatchTrailingSlashMode === Router::TRAILING_SLASH_ALWAYS) {
                $dispatchRequestPath = $dispatchRequestPath . '/';
            }

            if ('' === $dispatchRequestPath) {
                $dispatchRequestPath = '/';
            }
        }

        if ($dispatchRequestPath !== $contractRequestPath) {
            $dispatchRequestUri = $dispatchRequestPath;

            if ($contractRequestHttpPath->hasQueryString($queryString)) {
                $dispatchRequestUri .= "?{$queryString}";
            }
            if ($contractRequestHttpPath->hasFragment($fragment)) {
                $dispatchRequestUri .= "#{$fragment}";
            }
        }

        $this->dispatchContract = $dispatchContract;
        $this->dispatchRequestMethod = $dispatchRequestMethod;
        $this->dispatchRequestUri = $dispatchRequestUri;
        $this->dispatchRequestPath = $dispatchRequestPath;

        $this->dispatchRoute = null;
        $this->dispatchActionAttributes = [];

        $dispatchRouteIndex = [];

        $dispatchActionAttributes = [];
        $dispatchMiddlewareList = [];
        $dispatchFallbackList = [];

        $routeNodeCurrent = $this->rootRouterNode;

        $routeSubpathCurrent = [ '' ];
        $routeSubpathList = [ '/' ];

        $slice = $dispatchRequestPath;
        $slice = ltrim($slice, '/');
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

                $routeSubpathCurrent[] = $requestUriPart;
                $routeSubpathList[] = implode('/', $routeSubpathCurrent);

            } else {
                if (isset($routeNodeCurrent->childrenByPart[ $requestUriPart ])) {
                    $routeNodeCurrent = $routeNodeCurrent->childrenByPart[ $requestUriPart ];

                    $routeSubpathCurrent[] = $routeNodeCurrent->part;
                    $routeSubpathList[] = implode('/', $routeSubpathCurrent);

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

                        $routeSubpathCurrent[] = $routeNodeCurrent->part;
                        $routeSubpathList[] = implode('/', $routeSubpathCurrent);

                        continue 2;
                    }
                }

                $routeNodeCurrent = null;

                break;
            }
        }

        $middlewareIndexes = [
            'id'   => [],
            'path' => [],
            'tag'  => [],
        ];
        $fallbackIndexes = [
            'id'   => [],
            'path' => [],
            'tag'  => [],
        ];
        foreach ( $routeSubpathList as $routeSubpath ) {
            if (isset($middlewareCollection->middlewareIndexByRoutePath[ $routeSubpath ])) {
                $middlewareIndexes[ 'path' ][ $routeSubpath ] = $middlewareCollection->middlewareIndexByRoutePath[ $routeSubpath ];
            }

            if (isset($fallbackCollection->fallbackIndexByRoutePath[ $routeSubpath ])) {
                $fallbackIndexes[ 'path' ][ $routeSubpath ] = $fallbackCollection->fallbackIndexByRoutePath[ $routeSubpath ];
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
            $dispatchRoute = $routeCollection->routeList[ $dispatchRouteId ];
            $dispatchRouteClone = clone $dispatchRoute;
        }

        if (null !== $dispatchRouteClone) {
            $routeId = $dispatchRouteClone->id;
            $routePath = $dispatchRouteClone->path;


            if (isset($middlewareCollection->middlewareIndexByRouteId[ $routeId ])) {
                $middlewareIndexes[ 'id' ][ $routeId ] = $middlewareCollection->middlewareIndexByRouteId[ $routeId ];
            }

            if (isset($fallbackCollection->fallbackIndexByRouteId[ $routeId ])) {
                $fallbackIndexes[ 'id' ][ $routeId ] = $fallbackCollection->fallbackIndexByRouteId[ $routeId ];
            }


            if (isset($middlewareCollection->middlewareIndexByRoutePath[ $routePath ])) {
                $middlewareIndexes[ 'path' ][ $routePath ] = $middlewareCollection->middlewareIndexByRoutePath[ $routePath ];
            }

            if (isset($fallbackCollection->fallbackIndexByRoutePath[ $routePath ])) {
                $fallbackIndexes[ 'path' ][ $routePath ] = $fallbackCollection->fallbackIndexByRoutePath[ $routePath ];
            }


            foreach ( $dispatchRouteClone->tagIndex as $tag => $bool ) {
                if (isset($middlewareCollection->middlewareIndexByRouteTag[ $tag ])) {
                    $middlewareIndexes[ 'tag' ][ $tag ] = $middlewareCollection->middlewareIndexByRouteTag[ $tag ];
                }

                if (isset($fallbackCollection->fallbackIndexByRouteTag[ $tag ])) {
                    $fallbackIndexes[ 'tag' ][ $tag ] = $fallbackCollection->fallbackIndexByRouteTag[ $tag ];
                }
            }
        }

        $fnSortStrlenDesc = static function ($a, $b) {
            return strlen($b) <=> strlen($a);
        };

        uksort($middlewareIndexes[ 'path' ], $fnSortStrlenDesc);
        uksort($fallbackIndexes[ 'path' ], $fnSortStrlenDesc);

        $middlewareIndex = [];
        foreach ( $middlewareIndexes[ 'id' ] as $index ) {
            $middlewareIndex += $index;
        }
        foreach ( $middlewareIndexes[ 'path' ] as $index ) {
            $middlewareIndex += $index;
        }
        foreach ( $middlewareIndexes[ 'tag' ] as $index ) {
            $middlewareIndex += $index;
        }

        $fallbackIndex = [];
        foreach ( $fallbackIndexes[ 'id' ] as $index ) {
            $fallbackIndex += $index;
        }
        foreach ( $fallbackIndexes[ 'path' ] as $index ) {
            $fallbackIndex += $index;
        }
        foreach ( $fallbackIndexes[ 'tag' ] as $index ) {
            $fallbackIndex += $index;
        }

        foreach ( $middlewareIndex as $i => $bool ) {
            $dispatchMiddlewareList[ $i ] = $middlewareCollection->middlewareList[ $i ];
        }

        foreach ( $fallbackIndex as $i => $bool ) {
            $dispatchFallbackList[ $i ] = $fallbackCollection->fallbackList[ $i ];
        }

        $fnPipelineCallGenericHandler = $this->fnPipelineCallGenericHandler();

        $pipeline = $theFunc->newPipe();
        $pipeline
            ->setContext($pipeContext)
            ->setFnCallUserFuncArray($fnPipelineCallGenericHandler)
        ;

        $pipelineChild = $pipeline;
        foreach ( $dispatchMiddlewareList as $middleware ) {
            $pipelineChild = $pipelineChild->middleware($middleware);
        }

        if ($dispatchRouteClone) {
            $this->dispatchRoute = $dispatchRouteClone;
            $this->dispatchActionAttributes = $dispatchActionAttributes;

            $dispatchRouteClone->dispatchContract = $dispatchContract;
            $dispatchRouteClone->dispatchRequestMethod = $dispatchRequestMethod;
            $dispatchRouteClone->dispatchRequestUri = $dispatchRequestUri;
            $dispatchRouteClone->dispatchRequestPath = $dispatchRequestPath;

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
                . '[ ' . $contractRequestMethod . ' ]'
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
                [ 'Unhandled exception during dispatch', $e ], $e
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

    public function getDispatchRequestPath() : string
    {
        return $this->dispatchRequestPath;
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
