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


    public function __construct(RouterCacheConfig $config)
    {
        $this->config = $config;
        $this->config->validate();
    }


    public function loadCache() : ?array
    {
        if (static::CACHE_MODE_STORAGE !== $this->config->cacheMode) return null;

        $cacheData = null;

        if ($this->config->cacheAdapter) {
            try {
                $cacheItem = $this->cacheAdapterGetItem();

                if ($cacheItem->isHit()) {
                    $cacheData = $cacheItem->get();
                }
            }
            catch ( \Throwable $e ) {
            }

        } elseif ($this->config->cacheDirpath) {
            $cacheFilepath = "{$this->config->cacheDirpath}/{$this->config->cacheFilename}";

            $before = error_reporting(0);
            if (@is_file($cacheFilepath)) {
                $content = @file_get_contents($cacheFilepath);

                if (false === $content) {
                    throw new RuntimeException(
                        'Unable to read file: ' . $cacheFilepath
                    );
                }

                $cacheData = $content;
                $cacheData = $this->unserializeCacheData($cacheData);

                if (false
                    || (false === $cacheData)
                    || (is_object($cacheData) && (get_class($cacheData) === '__PHP_Incomplete_Class'))
                ) {
                    $cacheData = null;
                }
            }
            error_reporting($before);
        }

        return $cacheData;
    }

    public function saveCache(array $cacheData) // : static
    {
        if (static::CACHE_MODE_STORAGE !== $this->config->cacheMode) return $this;

        if ($this->config->cacheAdapter) {
            $cacheItem = $this->cacheAdapterGetItem();
            $cacheItem->set($cacheData);

            $this->config->cacheAdapter->save($cacheItem);

        } elseif ($this->config->cacheDirpath) {
            $cacheFilepath = "{$this->config->cacheDirpath}/{$this->config->cacheFilename}";

            $content = $this->serializeCacheData($cacheData);

            $status = false;
            $before = error_reporting(0);
            try {
                if (! @is_dir($this->config->cacheDirpath)) {
                    @mkdir($this->config->cacheDirpath, 0775, true);
                }
                $status = @file_put_contents($cacheFilepath, $content);
            }
            catch ( \Throwable $e ) {
            }
            error_reporting($before);

            if (! $status) {
                throw new RuntimeException(
                    'Unable to write file: ' . $cacheFilepath
                );
            }
        }

        return $this;
    }

    public function clearCache() // : static
    {
        if (static::CACHE_MODE_STORAGE !== $this->config->cacheMode) return $this;

        if ($this->config->cacheAdapter) {
            $cacheAdapter = $this->config->cacheAdapter;

            $cacheAdapter->clear();

        } elseif ($this->config->cacheDirpath) {
            $cacheFilepath = "{$this->config->cacheDirpath}/{$this->config->cacheFilename}";

            $status = true;
            $before = error_reporting(0);
            try {
                if (@is_file($cacheFilepath)) {
                    $status = @unlink($cacheFilepath);
                }
            }
            catch ( \Throwable $e ) {
            }
            error_reporting($before);

            if (! $status) {
                throw new RuntimeException(
                    'Unable to delete file: ' . $cacheFilepath
                );
            }
        }

        return $this;
    }


    /**
     * @return \Psr\Cache\CacheItemInterface|null
     */
    protected function cacheAdapterGetItem() // : \Psr\Cache\CacheItemInterface
    {
        if (static::CACHE_MODE_STORAGE !== $this->config->cacheMode) return null;
        if (null === $this->config->cacheAdapter) return null;

        try {
            $cacheItem = $this->config->cacheAdapter->getItem(__CLASS__);
        }
        catch ( \Psr\Cache\InvalidArgumentException $e ) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        return $cacheItem;
    }


    protected function serializeCacheData(array $cacheData) // : false|string
    {
        return @serialize($cacheData);
    }

    protected function unserializeCacheData(string $cacheData) // : false|array|__PHP_Incomplete_Class
    {
        return @unserialize($cacheData);
    }
}
