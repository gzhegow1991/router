<?php
/**
 * @noinspection PhpFullyQualifiedNameUsageInspection
 * @noinspection PhpUndefinedClassInspection
 * @noinspection PhpUndefinedNamespaceInspection
 */

namespace Gzhegow\Router\Core\Cache;

use Gzhegow\Lib\Lib;
use Gzhegow\Lib\Config\AbstractConfig;
use Gzhegow\Router\Exception\LogicException;


/**
 * @property string|null                                   $cacheMode
 *
 * @property object|\Psr\Cache\CacheItemPoolInterface|null $cacheAdapter
 *
 * @property string|null                                   $cacheDirpath
 * @property string|null                                   $cacheFilename
 *
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class RouterCacheConfig extends AbstractConfig
{
    /**
     * > тип кеширования - кешировать или не использовать кэш
     *
     * @see RouterCache::LIST_CACHE_MODE
     *
     * @var string|null
     */
    protected $cacheMode = RouterCache::CACHE_MODE_NO_CACHE;

    /**
     * > можно установить пакет `composer require symfony/cache` и использовать адаптер, чтобы хранить кэш в redis или любым другим способом
     *
     * @var object|\Psr\Cache\CacheItemPoolInterface|null
     */
    protected $cacheAdapter;

    /**
     * > для кэша можно использовать путь к файлу, в этом случае кеш будет сделан через file_{get|put}_contents() + (un)serialize()
     *
     * @var string|null
     */
    protected $cacheDirpath = __DIR__ . '/../var/cache/gzhegow.router';
    /**
     * > для кэша можно использовать путь к файлу, в этом случае кеш будет сделан через file_{get|put}_contents() + (un)serialize()
     *
     * @var string|null
     */
    protected $cacheFilename = 'router.cache';


    protected function validation(array $context = []) : bool
    {
        $theType = Lib::type();

        if (null !== $this->cacheAdapter) {
            if (! is_a($this->cacheAdapter, $class = '\Psr\Cache\CacheItemPoolInterface')) {
                throw new LogicException(
                    [ 'The `cacheAdapter` should be instance of: ' . $class, $this ]
                );
            }
        }

        if (null !== $this->cacheDirpath) {
            $theType->dirpath($this->cacheDirpath, true)->orThrow();
        }

        if (null !== $this->cacheFilename) {
            $theType->filename($this->cacheFilename)->orThrow();
        }

        if (! isset(RouterCache::LIST_CACHE_MODE[ $this->cacheMode ])) {
            throw new LogicException(
                [
                    ''
                    . 'The `cacheMode` should be one of: '
                    . '[ ' . implode(' ][ ', array_keys(RouterCache::LIST_CACHE_MODE)) . ' ]',
                    //
                    $this,
                ]
            );
        }

        return true;
    }
}
