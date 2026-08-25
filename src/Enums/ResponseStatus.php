<?php

namespace SmartDato\CblLogistica\Enums;

/**
 * The status field of a CreateShipment response.
 */
enum ResponseStatus: string
{
    case Ok = 'OK';
    case Error = 'ERROR';
}
