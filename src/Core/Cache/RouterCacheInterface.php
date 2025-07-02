<?php

namespace Gzhegow\Router\Core\Cache;


interface RouterCacheInterface
{
    public function loadCache() : ?array;

    /**
     * @return static
     */
    public function saveCache(array $cacheData);

    /**
     * @return static
     */
    public function clearCache();
}
