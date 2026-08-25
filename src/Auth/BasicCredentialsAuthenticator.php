<?php

namespace SmartDato\CblLogistica\Auth;

use Saloon\Contracts\Authenticator;
use Saloon\Http\PendingRequest;
use SmartDato\CblLogistica\Data\Credentials;

/**
 * Basic auth only, for the token endpoint — it authenticates like every other CBL
 * call but must not carry a dailyToken, which is what it is being asked to issue.
 */
class BasicCredentialsAuthenticator implements Authenticator
{
    public function __construct(
        protected readonly Credentials $credentials,
    ) {}

    public function set(PendingRequest $pendingRequest): void
    {
        $pendingRequest->headers()->add('Authorization', 'Basic '.base64_encode(
            $this->credentials->username.':'.$this->credentials->password,
        ));
    }
}
