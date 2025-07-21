<?php
/**
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 */

namespace Gzhegow\Router\Core\Cache;

use Gzhegow\Lib\Lib;
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


    public function __construct(
        RouterCacheConfig $config
    )
    {
        $this->config = $config;
        $this->config->validate();
    }


    public function loadCache() : ?array
    {
        if (static::CACHE_MODE_STORAGE !== $this->config->cacheMode) {
            return null;
        }

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
            $theFsFile = Lib::fsFile();
            $thePhp = Lib::php();

            $cacheFilepath = "{$this->config->cacheDirpath}/{$this->config->cacheFilename}";

            $content = $theFsFile->file_get_contents($cacheFilepath);

            if (! (false
                || (null === $content)
                || (false === $content)
            )) {
                $cacheData = $thePhp->unserialize($content);
            }
        }

        return $cacheData;
    }

    /**
     * @return static
     */
    public function saveCache(array $cacheData)
    {
        if (static::CACHE_MODE_STORAGE !== $this->config->cacheMode) {
            return $this;
        }

        if ($this->config->cacheAdapter) {
            $cacheItem = $this->cacheAdapterGetItem();
            $cacheItem->set($cacheData);

            $this->config->cacheAdapter->save($cacheItem);

        } elseif ($this->config->cacheDirpath) {
            $theFsFile = Lib::fsFile();
            $thePhp = Lib::php();

            $cacheFilepath = "{$this->config->cacheDirpath}/{$this->config->cacheFilename}";

            $content = $thePhp->serialize($cacheData);

            $theFsFile->file_put_contents(
                $cacheFilepath, $content, null,
                [], [ 0775 ]
            );
        }

        return $this;
    }

    /**
     * @return static
     */
    public function clearCache()
    {
        if (static::CACHE_MODE_STORAGE !== $this->config->cacheMode) {
            return $this;
        }

        if ($this->config->cacheAdapter) {
            $cacheAdapter = $this->config->cacheAdapter;

            $cacheAdapter->clear();

        } elseif ($this->config->cacheDirpath) {
            $theFsFile = Lib::fsFile();

            $cacheFilepath = "{$this->config->cacheDirpath}/{$this->config->cacheFilename}";

            $theFsFile->rmf($cacheFilepath);
        }

        return $this;
    }


    /**
     * @return \Psr\Cache\CacheItemInterface|null
     *
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    protected function cacheAdapterGetItem()
    {
        if (static::CACHE_MODE_STORAGE !== $this->config->cacheMode) {
            return null;
        }

        if (null === $this->config->cacheAdapter) {
            return null;
        }

        $cacheKey = preg_replace(
            '/[' . preg_quote('{}()/\@:', '/') . ']/',
            '.',
            __CLASS__
        );

        try {
            $cacheItem = $this->config->cacheAdapter->getItem($cacheKey);
        }
        catch ( \Psr\Cache\InvalidArgumentException $e ) {
            throw new RuntimeException($e);
        }

        return $cacheItem;
    }
}
