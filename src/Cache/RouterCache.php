<?php
/**
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 */

namespace Gzhegow\Router\Cache;

use Gzhegow\Router\Lib;
use Gzhegow\Router\Route\RouteCompiled;
use Gzhegow\Router\Exception\LogicException;
use Gzhegow\Router\Exception\RuntimeException;
use Gzhegow\Router\Cache\Struct\RouterCacheRuntime;


class RouterCache implements RouterCacheInterface
{
    const CACHE_MODE_NO_CACHE = 'NO_CACHE';
    const CACHE_MODE_STORAGE  = 'STORAGE';

    const LIST_CACHE_MODE = [
        self::CACHE_MODE_NO_CACHE => true,
        self::CACHE_MODE_STORAGE  => true,
    ];


    /**
     * @var string @see \Gzhegow\Router\Cache\RouterCache::LIST_CACHE_MODE
     */
    protected $cacheMode = self::CACHE_MODE_NO_CACHE;
    /**
     * @var object|\Psr\Cache\CacheItemPoolInterface
     */
    protected $cacheAdapter;
    /**
     * @var string
     */
    protected $cacheDirpath = __DIR__ . '/../var/cache/app.router';
    /**
     * @var string
     */
    protected $cacheFilename = 'router.cache';

    /**
     * @var object|\Psr\Cache\CacheItemInterface
     */
    protected $cacheAdapterItem;


    /**
     * @param string|null                                   $cacheMode @see \Gzhegow\Router\Cache\RouterCache::LIST_CACHE_MODE
     * @param object|\Psr\Cache\CacheItemPoolInterface|null $cacheAdapter
     * @param string|null                                   $cacheDirpath
     * @param string|null                                   $cacheFilename
     *
     * @noinspection PhpUndefinedNamespaceInspection
     * @noinspection PhpUndefinedClassInspection
     */
    public function setCacheSettings(
        string $cacheMode = null,
        object $cacheAdapter = null,
        string $cacheDirpath = null,
        string $cacheFilename = null
    ) // : static
    {
        if ((null !== $cacheMode) && ! isset(static::LIST_CACHE_MODE[ $cacheMode ])) {
            throw new LogicException(
                'The `cacheMode` should be one of: ' . implode('|', array_keys(static::LIST_CACHE_MODE))
                . ' / ' . $cacheMode
            );
        }

        if ((null !== $cacheAdapter) && ! is_a($cacheAdapter, $class = '\Psr\Cache\CacheItemPoolInterface')) {
            throw new LogicException(
                'The `cacheAdapter` should be instance of: ' . $class
                . ' / ' . Lib::php_dump($cacheAdapter)
            );
        }

        if ((null !== $cacheDirpath) && (null === Lib::filter_dirpath($cacheDirpath))) {
            throw new LogicException(
                'The `cacheDirpath` should be valid directory path: ' . $cacheDirpath
            );
        }

        if ((null !== $cacheFilename) && (null === Lib::filter_filename($cacheFilename))) {
            throw new LogicException(
                'The `cacheFilename` should be valid filename: ' . $cacheFilename
            );
        }

        $this->cacheMode = $cacheMode ?? static::CACHE_MODE_NO_CACHE;
        $this->cacheAdapter = $cacheAdapter;
        $this->cacheDirpath = $cacheDirpath ?? __DIR__ . '/../../var/cache/app.router';
        $this->cacheFilename = $cacheFilename ?? 'router.cache';

        return $this;
    }


    public function initCache() // : static
    {
        if ($this->cacheMode !== static::CACHE_MODE_STORAGE) return $this;
        if (! $this->cacheAdapter) return $this;
        if ($this->cacheAdapterItem) return $this;

        try {
            $cacheItem = $this->cacheAdapter->getItem(__CLASS__);
        }
        catch ( \Psr\Cache\InvalidArgumentException $e ) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        $this->cacheAdapterItem = $cacheItem;

        return $this;
    }

    public function loadCache() : ?array
    {
        $this->initCache();

        $cacheData = null;

        if ($this->cacheMode === static::CACHE_MODE_STORAGE) {
            if ($this->cacheAdapter) {
                try {
                    if ($this->cacheAdapterItem->isHit()) {
                        $cacheData = $this->cacheAdapterItem->get();
                    }
                }
                catch ( \Throwable $e ) {
                    $cacheData = null;
                }

            } elseif ($this->cacheDirpath) {
                $cacheFilepath = "{$this->cacheDirpath}/{$this->cacheFilename}";

                $before = error_reporting(0);
                if (@is_file($cacheFilepath)) {
                    if (false !== ($content = @file_get_contents($cacheFilepath))) {
                        $cacheData = $content;

                    } else {
                        throw new RuntimeException(
                            'Unable to read file: ' . $cacheFilepath
                        );
                    }

                    $cacheData = $this->unserializeCacheData($cacheData);

                    if (false === $cacheData) {
                        $cacheData = null;

                    } elseif (get_class($cacheData) === '__PHP_Incomplete_Class') {
                        $cacheData = null;
                    }
                }
                error_reporting($before);
            }
        }

        return $cacheData;
    }

    public function clearCache() // : static
    {
        $this->initCache();

        if ($this->cacheMode === static::CACHE_MODE_STORAGE) {
            if ($this->cacheAdapter) {
                $cacheAdapter = $this->cacheAdapter;

                $cacheAdapter->clear();

            } elseif ($this->cacheDirpath) {
                $cacheFilepath = "{$this->cacheDirpath}/{$this->cacheFilename}";

                $before = error_reporting(0);
                $status = true;
                if (@is_file($cacheFilepath)) {
                    $status = @unlink($cacheFilepath);
                }
                error_reporting($before);

                if (! $status) {
                    throw new RuntimeException(
                        'Unable to delete file: ' . $cacheFilepath
                    );
                }
            }
        }

        return $this;
    }

    public function saveCache(array $cacheData) // : static
    {
        $this->initCache();

        if ($this->cacheMode === static::CACHE_MODE_STORAGE) {
            if ($this->cacheAdapter) {
                $this->cacheAdapterItem->set($cacheData);
                $this->cacheAdapter->save($this->cacheAdapterItem);

            } elseif ($this->cacheDirpath) {
                $cacheFilepath = "{$this->cacheDirpath}/{$this->cacheFilename}";

                $content = $this->serializeCacheData($cacheData);

                $before = error_reporting(0);
                if (! @is_dir($this->cacheDirpath)) {
                    @mkdir($this->cacheDirpath, 0775, true);
                }
                $status = @file_put_contents($cacheFilepath, $content);
                error_reporting($before);

                if (! $status) {
                    throw new RuntimeException(
                        'Unable to write file: ' . $cacheFilepath
                    );
                }
            }
        }

        return $this;
    }


    protected function serializeCacheData($cacheData) // : false|string
    {
        return @serialize($cacheData);
    }

    protected function unserializeCacheData($cacheData) // : false|array|__PHP_Incomplete_Class
    {
        return @unserialize($cacheData);
    }
}
