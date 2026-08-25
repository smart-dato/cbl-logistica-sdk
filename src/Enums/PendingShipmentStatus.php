<?php

namespace SmartDato\CblLogistica\Enums;

/**
 * The status of a GetPendingShipments entry.
 *
 * Pending means CBL is still waiting for packages to be registered; Closed means
 * the declared package count is complete and the shipment awaits confirmation.
 * Accounts without day-confirmation enabled never report Closed.
 */
enum PendingShipmentStatus: string
{
    case Pending = 'pending';
    case Closed = 'closed';
}
