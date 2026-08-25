<?php

namespace SmartDato\CblLogistica\Data;

use Spatie\LaravelData\Data;

/**
 * One CBL account. The username and password authenticate the HTTP request, the
 * client token buys the daily token, and the client code identifies the account
 * inside the four request bodies that carry it.
 *
 * Applications serving several accounts of the same carrier build one of these
 * per account and pass it to CblLogistica::withCredentials().
 */
final class Credentials extends Data
{
    public function __construct(
        public string $username,
        public string $password,
        public string $clientToken,
        public string $clientCode,
    ) {}

    /**
     * A stable fingerprint of the whole account, used to key the cached daily
     * token. Every field takes part so two accounts can never trade tokens.
     */
    public function fingerprint(): string
    {
        return sha1(implode('|', [
            $this->username,
            $this->password,
            $this->clientToken,
            $this->clientCode,
        ]));
    }
}
