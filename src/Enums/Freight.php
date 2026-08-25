<?php

namespace SmartDato\CblLogistica\Enums;

/**
 * The freight field of a CreateShipment request — who carries the cost.
 * CBL defaults to Debido when the field is omitted.
 */
enum Freight: string
{
    case Debido = 'D';
    case Pagado = 'P';
}
