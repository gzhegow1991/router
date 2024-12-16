<?php

namespace Gzhegow\Router\Core\Cache;


interface CacheInterface
{
    public function loadCache() : ?array;

    public function saveCache(array $cacheData);

    public function clearCache();
}
