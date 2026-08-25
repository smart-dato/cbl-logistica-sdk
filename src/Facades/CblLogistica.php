<?php

namespace SmartDato\CblLogistica\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \SmartDato\CblLogistica\CblLogistica withCredentials(\SmartDato\CblLogistica\Data\Credentials $credentials)
 * @method static \SmartDato\CblLogistica\CblLogistica withTokenStore(\SmartDato\CblLogistica\Contracts\TokenStore $tokenStore)
 * @method static \SmartDato\CblLogistica\Resources\ShipmentsResource shipments()
 * @method static \SmartDato\CblLogistica\Resources\StatusResource status()
 * @method static \SmartDato\CblLogistica\Resources\PodResource pod()
 * @method static \SmartDato\CblLogistica\Data\Credentials|null credentials()
 * @method static string dailyToken()
 *
 * @see \SmartDato\CblLogistica\CblLogistica
 */
class CblLogistica extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \SmartDato\CblLogistica\CblLogistica::class;
    }
}
