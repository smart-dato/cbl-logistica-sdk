<?php

declare(strict_types=1);

namespace SmartDato\CblLogistica\Cache;

use Illuminate\Contracts\Cache\Repository;
use SmartDato\CblLogistica\Auth\CblAuthenticator;
use SmartDato\CblLogistica\Contracts\TokenStore;

/**
 * Adapts a Laravel cache repository to {@see TokenStore}.
 *
 * Any number of CBL accounts may share one store — tokens are keyed per account
 * by {@see CblAuthenticator}.
 */
final readonly class LaravelTokenStore implements TokenStore
{
    public function __construct(
        private Repository $cache,
    ) {}

    public function get(string $key): ?string
    {
        $value = $this->cache->get($key);

        return is_string($value) ? $value : null;
    }

    public function put(string $key, string $value, int $ttlSeconds): void
    {
        $this->cache->put($key, $value, $ttlSeconds);
    }
}
