<?php

declare(strict_types=1);

namespace SmartDato\CblLogistica\Contracts;

interface TokenStore
{
    public function get(string $key): ?string;

    public function put(string $key, string $value, int $ttlSeconds): void;
}
