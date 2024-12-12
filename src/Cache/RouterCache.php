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

            $cacheData = $this->fileGetCache($cacheFilepath);

            if (null !== $cacheData) {
                $cacheData = $this->unserializeCacheData($cacheData);
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

            $content = $this->serializeCacheData($cacheData);

            $this->filePutCache($cacheFilepath, $content);
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

            $this->fileUnlinkCache($cacheFilepath);
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


    protected function fileGetCache(string $file) : ?string
    {
        $cacheData = null;

        $before = error_reporting(0);

        if (@is_file($file)) {
            try {
                $cacheData = @file_get_contents($file);
            }
            catch ( \Throwable $e ) {
                $cacheData = false;
            }

            if (false === $cacheData) {
                throw new RuntimeException(
                    'Unable to read file: ' . $file
                );
            }
        }

        error_reporting($before);

        return $cacheData;
    }

    protected function filePutCache(string $file, string $content) : bool
    {
        $before = error_reporting(0);

        try {
            if (! @is_dir($this->config->cacheDirpath)) {
                @mkdir($this->config->cacheDirpath, 0775, true);
            }

            $status = @file_put_contents($file, $content);
        }
        catch ( \Throwable $e ) {
            $status = false;
        }

        error_reporting($before);

        if (! $status) {
            throw new RuntimeException(
                'Unable to write file: ' . $file
            );
        }

        return $status;
    }

    protected function fileUnlinkCache(string $file) : bool
    {
        $status = true;

        $before = error_reporting(0);

        try {
            if (@is_file($file)) {
                $status = @unlink($file);
            }
        }
        catch ( \Throwable $e ) {
            $status = false;
        }

        if (! $status) {
            throw new RuntimeException(
                'Unable to delete file: ' . $file
            );
        }

        error_reporting($before);

        return $status;
    }


    protected function serializeCacheData(array $data) : ?string
    {
        $before = error_reporting(0);

        try {
            $result = @serialize($data);
        }
        catch ( \Throwable $e ) {
            $result = false;
        }

        if (false === $result) {
            throw new RuntimeException(
                'Unable to serialize data: ' . Lib::php_dump($data)
            );
        }

        error_reporting($before);

        return $result;
    }

    protected function unserializeCacheData(string $data) : ?array
    {
        $before = error_reporting(0);

        try {
            $result = @unserialize($data);
        }
        catch ( \Throwable $e ) {
            $result = false;
        }

        if (false
            || (false === $result)
            || (is_object($result) && (get_class($result) === '__PHP_Incomplete_Class'))
        ) {
            $result = null;
        }

        error_reporting($before);

        return $result;
    }
}
