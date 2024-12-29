<?php

namespace Gzhegow\Router\Core;

use Gzhegow\Lib\Config\AbstractConfig;
use Gzhegow\Router\Core\Route\Struct\HttpMethod;
use Gzhegow\Router\Core\Cache\RouterCacheConfig;
use Gzhegow\Router\Exception\LogicException;


/**
 * @property RouterCacheConfig $cache
 *
 * @property bool              $registerAllowObjectsAndClosures
 * @property int               $compileTrailingSlashMode
 * @property bool              $dispatchIgnoreMethod
 * @property string|HttpMethod $dispatchForceMethod
 * @property int               $dispatchTrailingSlashMode
 */
class RouterConfig extends AbstractConfig
{
    /**
     * @var RouterCacheConfig
     */
    protected $cache;

    /**
     * > false -> чтобы работал кеш, ибо объекты runtime и замыкания нельзя сохранить в файл
     *
     * @var bool
     */
    protected $registerAllowObjectsAndClosures = false;
    /**
     * > бросать исключение при попытке зарегистрировать роут без/с trailing-slash
     *
     * @see Router::LIST_TRAILING_SLASH
     *
     * @var int
     */
    protected $compileTrailingSlashMode = Router::TRAILING_SLASH_AS_IS;
    /**
     * > true -> не учитывать метод запроса при выполнении маршрута, удобно тестировать POST/OPTIONS/HEAD запросы в браузере (сработает первый зарегистрированный!
     *
     * @var bool
     */
    protected $dispatchIgnoreMethod = false;
    /**
     * > 'GET|POST|PUT|OPTIONS', чтобы принудительно установить метод запроса при выполнении действия
     *
     * @var HttpMethod|string
     */
    protected $dispatchForceMethod;
    /**
     * > автоматически доставлять или убирать trailing-slash на этапе маршрутизации
     *
     * @var bool
     */
    protected $dispatchTrailingSlashMode = Router::TRAILING_SLASH_AS_IS;


    public function __construct()
    {
        $this->__sections[ 'cache' ] = $this->cache = new RouterCacheConfig();
    }


    public function validate() : void
    {
        $this->cache->validate();

        $this->registerAllowObjectsAndClosures = (bool) $this->registerAllowObjectsAndClosures;
        $this->dispatchIgnoreMethod = (bool) $this->dispatchIgnoreMethod;

        if (null !== $this->dispatchForceMethod) {
            $this->dispatchForceMethod = HttpMethod::from($this->dispatchForceMethod)->getValue();
        }

        if (! isset(Router::LIST_TRAILING_SLASH[ $this->compileTrailingSlashMode ])) {
            throw new LogicException(
                [
                    'The `compileTrailingSlashMode` should be one of: '
                    . implode(',', array_keys(Router::LIST_TRAILING_SLASH)),
                    $this,
                ]
            );
        }

        if (! isset(Router::LIST_TRAILING_SLASH[ $this->dispatchTrailingSlashMode ])) {
            throw new LogicException(
                [
                    'The `dispatchTrailingSlashMode` should be one of: '
                    . implode(',', array_keys(Router::LIST_TRAILING_SLASH)),
                    $this,
                ]
            );
        }
    }
}
