<?php
/**
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 */

namespace Gzhegow\Router\Cache;


interface RouterCacheInterface
{
    public function getConfig() : RouterCacheConfig;

    /**
     * @param callable $fn
     *
     * @return static
     */
    public function setConfig($fn); // : static

    /**
     * @return static
     */
    public function resetConfig(); // : static


    public function initCache();

    public function loadCache() : ?array;

    public function clearCache();

    public function saveCache(array $cacheData);
}
