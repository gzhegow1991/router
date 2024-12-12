<?php
/**
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 */

namespace Gzhegow\Router\Cache;

use Gzhegow\Lib\Lib;
use Gzhegow\Lib\Config\Config;
use Gzhegow\Router\Config\AbstractConfig;
use Gzhegow\Router\Exception\LogicException;


/**
 * @property string|null                                   $cacheMode
 * @property object|\Psr\Cache\CacheItemPoolInterface|null $cacheAdapter
 * @property string|null                                   $cacheDirpath
 * @property string|null                                   $cacheFilename
 */
class RouterCacheConfig extends Config
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
     * > адаптер кэширования, psr\cache
     *
     * @noinspection PhpFullyQualifiedNameUsageInspection
     * @noinspection PhpUndefinedClassInspection
     * @noinspection PhpUndefinedNamespaceInspection
     *
     * @var object|\Psr\Cache\CacheItemPoolInterface|null
     */
    protected $cacheAdapter = null;
    /**
     * > путь для файлового кэша в случае отстутствия адаптера
     *
     * @var string|null
     */
    protected $cacheDirpath = __DIR__ . '/../var/cache/gzhegow.router';
    /**
     * > имя файла для файлового кэша в случае отстутствия адаптера
     *
     * @var string|null
     */
    protected $cacheFilename = 'router.cache';


    public function validate() : void
    {
        if ((null !== $this->cacheMode)
            && ! isset(RouterCache::LIST_CACHE_MODE[ $this->cacheMode ])
        ) {
            throw new LogicException(
                'The `cacheMode` should be one of: '
                . implode('|', array_keys(RouterCache::LIST_CACHE_MODE))
                . ' / ' . $this->cacheMode
            );
        }

        if ((null !== $this->cacheAdapter)
            && ! is_a($this->cacheAdapter, $class = '\Psr\Cache\CacheItemPoolInterface')
        ) {
            throw new LogicException(
                [
                    'The `cacheAdapter` should be instance of: ' . $class,
                    $this->cacheAdapter,
                ]
            );
        }

        if ((null !== $this->cacheDirpath)
            && (null === Lib::parse_dirpath($this->cacheDirpath))
        ) {
            throw new LogicException(
                [
                    'The `cacheDirpath` should be valid directory path',
                    $this->cacheDirpath,
                ]
            );
        }

        if ((null !== $this->cacheFilename)
            && (null === Lib::parse_filename($this->cacheFilename))
        ) {
            throw new LogicException(
                'The `cacheFilename` should be valid filename: ' . $this->cacheFilename
            );
        }
    }
}
