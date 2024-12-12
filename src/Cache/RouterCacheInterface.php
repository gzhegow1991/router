<?php

namespace Gzhegow\Router\Cache;


interface RouterCacheInterface
{
    public function loadCache() : ?array;

    public function saveCache(array $cacheData);

    public function clearCache();
}
