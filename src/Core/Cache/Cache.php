<?php
/**
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 */

namespace Gzhegow\Router\Core\Cache;

use Gzhegow\Lib\Lib;
use Gzhegow\Router\Core\Exception\RuntimeException;


class Cache implements CacheInterface
{
    const CACHE_MODE_NO_CACHE = 'NO_CACHE';
    const CACHE_MODE_STORAGE  = 'STORAGE';

    const LIST_CACHE_MODE = [
        self::CACHE_MODE_NO_CACHE => true,
        self::CACHE_MODE_STORAGE  => true,
    ];


    /**
     * @var CacheConfig
     */
    protected $config;


    public function __construct(CacheConfig $config)
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

            $content = null;
            if (is_file($cacheFilepath)) {
                $content = Lib::fs_file_get_contents($cacheFilepath);
            }

            if (null !== $content) {
                $cacheData = Lib::php_unserialize($content);
            }
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

            $content = Lib::php_serialize($cacheData);

            Lib::fs_file_put_contents($cacheFilepath, $content, [ 0775, true ]);
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

            Lib::fs_rm($cacheFilepath);
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
}
