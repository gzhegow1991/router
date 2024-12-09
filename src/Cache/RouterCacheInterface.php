<?php

namespace Gzhegow\Router\Cache;


interface RouterCacheInterface
{
    public function initCache();

    public function loadCache() : ?array;

    public function clearCache();

    public function saveCache(array $cacheData);
}
