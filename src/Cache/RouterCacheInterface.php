<?php
/**
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 */

namespace Gzhegow\Router\Cache;

interface RouterCacheInterface
{
    /**
     * @noinspection PhpFullyQualifiedNameUsageInspection
     *
     * @param string|null                                   $cacheMode @see \Gzhegow\Router\Cache\RouterCache::LIST_CACHE_MODE
     * @param object|\Psr\Cache\CacheItemPoolInterface|null $cacheAdapter
     * @param string|null                                   $cacheDirpath
     * @param string|null                                   $cacheFilename
     */
    public function setCacheSettings(
        string $cacheMode = null,
        object $cacheAdapter = null,
        string $cacheDirpath = null,
        string $cacheFilename = null
    );


    public function initCache();

    public function loadCache() : ?array;

    public function clearCache();

    public function saveCache(array $cacheData);
}
