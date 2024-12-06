<?php
/**
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 */

namespace Gzhegow\Router\Cache;

use Gzhegow\Router\Exception\RuntimeException;


class RouterCache implements RouterCacheInterface
{
    const CACHE_MODE_NO_CACHE = 'NO_CACHE';
    const CACHE_MODE_STORAGE  = 'STORAGE';

    const LIST_CACHE_MODE = [
        self::CACHE_MODE_NO_CACHE => true,
        self::CACHE_MODE_STORAGE  => true,
    ];


    /**
     * @var RouterCacheConfig
     */
    protected $config;

    /**
     * @var object|\Psr\Cache\CacheItemInterface
     */
    protected $cacheAdapterItem;


    public function __construct(RouterCacheConfig $config)
    {
        $this->config = $config;
        $this->config->validate();
    }


    /**
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function initCache() // : static
    {
        if ($this->config->cacheMode !== static::CACHE_MODE_STORAGE) return $this;
        if (! $this->config->cacheAdapter) return $this;
        if ($this->cacheAdapterItem) return $this;

        try {
            $cacheItem = $this->config->cacheAdapter->getItem(__CLASS__);
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

        if ($this->config->cacheMode === static::CACHE_MODE_STORAGE) {
            if ($this->config->cacheAdapter) {
                try {
                    if ($this->cacheAdapterItem->isHit()) {
                        $cacheData = $this->cacheAdapterItem->get();
                    }
                }
                catch ( \Throwable $e ) {
                }

            } elseif ($this->config->cacheDirpath) {
                $cacheFilepath = "{$this->config->cacheDirpath}/{$this->config->cacheFilename}";

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
        if ($this->config->cacheMode === static::CACHE_MODE_STORAGE) {
            if ($this->config->cacheAdapter) {
                $cacheAdapter = $this->config->cacheAdapter;

                $cacheAdapter->clear();

            } elseif ($this->config->cacheDirpath) {
                $cacheFilepath = "{$this->config->cacheDirpath}/{$this->config->cacheFilename}";

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

        if ($this->config->cacheMode === static::CACHE_MODE_STORAGE) {
            if ($this->config->cacheAdapter) {
                $this->cacheAdapterItem->set($cacheData);

                $this->config->cacheAdapter->save($this->cacheAdapterItem);

            } elseif ($this->config->cacheDirpath) {
                $cacheFilepath = "{$this->config->cacheDirpath}/{$this->config->cacheFilename}";

                $content = $this->serializeCacheData($cacheData);

                $before = error_reporting(0);
                if (! @is_dir($this->config->cacheDirpath)) {
                    @mkdir($this->config->cacheDirpath, 0775, true);
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
