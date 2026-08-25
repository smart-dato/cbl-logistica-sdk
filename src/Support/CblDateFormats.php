<?php

namespace SmartDato\CblLogistica\Support;

/**
 * CBL is a .NET service and is not consistent about how it renders dates, so every
 * date property parses against a list rather than a single format.
 *
 * Observed on the wire: status and proof-of-delivery timestamps come back with no
 * timezone offset (`2026-06-19T12:50:42`), while the manual's CreateShipment sample
 * shows a full round-trip value with fractional seconds and an offset. The pending
 * shipment list is different again and uses `d/m/Y`.
 *
 * laravel-data's default `data.date_format` is offset-only, which rejects the values
 * CBL actually sends — hence these lists.
 */
final class CblDateFormats
{
    /**
     * @var list<string>
     */
    public const array DATETIME = [
        'Y-m-d\TH:i:s',
        'Y-m-d\TH:i:sP',
        'Y-m-d\TH:i:s.u',
        'Y-m-d\TH:i:s.uP',
        'Y-m-d H:i:s',
    ];

    /**
     * @var list<string>
     */
    public const array DATE = [
        'd/m/Y',
        'Y-m-d',
        ...self::DATETIME,
    ];

    /**
     * The format CBL is sent. Deliberately offset-free: these are delivery dates and
     * date-range bounds, which CBL reads in its own timezone.
     */
    public const string OUTGOING = 'Y-m-d\TH:i:s';
}
