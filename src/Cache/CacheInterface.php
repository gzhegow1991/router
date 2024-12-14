<?php

namespace Gzhegow\Router\Cache;


interface CacheInterface
{
    public function loadCache() : ?array;

    public function saveCache(array $cacheData);

    public function clearCache();
}
