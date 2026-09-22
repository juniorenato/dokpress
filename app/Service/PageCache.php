<?php

namespace App\Service;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Stores rendered pages on the filesystem for one hour.
 *
 * Entries live in `app/Cache` under the `wp_page_cache` namespace and are
 * tagged `page`. This service is not registered by the WordPress bootstrap.
 */
class PageCache
{
    /**
     * Tag-aware filesystem pool for this service.
     *
     * Typed as the adapter, not the contract: `clear()` exists on the adapter
     * and is absent from `TagAwareCacheInterface`.
     */
    private TagAwareAdapter $cache;

    /**
     * Creates the filesystem cache adapter.
     */
    public function __construct()
    {
        $this->cache = new TagAwareAdapter(
            new FilesystemAdapter(
                namespace: 'wp_page_cache',
                defaultLifetime: 3600,
                directory: WD_BASE_PATH . '/app/Cache'
            )
        );
    }

    /**
     * Returns a cached page, computing it on a miss.
     *
     * The stored item is tagged `page` so a later clear can target that tag.
     *
     * @param string            $key      Cache key for the page.
     * @param callable():string $callback Producer invoked on a cache miss.
     *
     * @return string Cached or freshly rendered page body.
     *
     * @throws \UnexpectedValueException When the callback does not return a string.
     * @throws \Psr\Cache\InvalidArgumentException When `$key` is not a valid cache key.
     */
    public function get(string $key, callable $callback): string
    {
        $page = $this->cache->get($key, function (ItemInterface $item) use ($callback): string {
            $item->tag(['page']);

            return $this->requireString($callback());
        });

        return $this->requireString($page);
    }

    /**
     * Deletes one cached page.
     *
     * @param string $key Cache key to remove.
     *
     * @return void
     *
     * @throws \Psr\Cache\InvalidArgumentException When `$key` is not a valid cache key.
     */
    public function invalidate(string $key): void
    {
        $this->cache->delete($key);
    }

    /**
     * Deletes every entry in this cache namespace.
     *
     * @return void
     */
    public function clearAll(): void
    {
        $this->cache->clear();
    }

    /**
     * Asserts that a cache value is a string.
     *
     * @param mixed $page Value produced by the cache or by the callback.
     *
     * @return string The same value when it is a string.
     *
     * @throws \UnexpectedValueException When `$page` is not a string.
     */
    private function requireString(mixed $page): string
    {
        if (!\is_string($page)) {
            throw new \UnexpectedValueException('The page cache callback must return a string.');
        }

        return $page;
    }
}
