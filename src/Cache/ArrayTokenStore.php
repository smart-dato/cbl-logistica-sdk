<?php

declare(strict_types=1);

namespace SmartDato\CblLogistica\Cache;

use SmartDato\CblLogistica\Contracts\TokenStore;

/**
 * An in-memory token store for tests and non-Laravel usage. Entries never expire
 * within the lifetime of the instance.
 */
final class ArrayTokenStore implements TokenStore
{
    /** @var array<string, string> */
    private array $tokens = [];

    public function get(string $key): ?string
    {
        return $this->tokens[$key] ?? null;
    }

    public function put(string $key, string $value, int $ttlSeconds): void
    {
        $this->tokens[$key] = $value;
    }
}
