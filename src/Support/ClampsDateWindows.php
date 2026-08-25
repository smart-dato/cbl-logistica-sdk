<?php

namespace SmartDato\CblLogistica\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

trait ClampsDateWindows
{
    /**
     * CBL clamps an over-wide window to dateTo minus the maximum and reports
     * warning 300. Clamping here too means the caller always knows which window
     * was actually queried, instead of inferring it from a warning.
     *
     * @return array{0: DateTimeInterface, 1: DateTimeInterface}
     */
    protected function clampWindow(DateTimeInterface $from, DateTimeInterface $to, int $maxDays): array
    {
        $earliest = CarbonImmutable::instance($to)->subDays($maxDays);

        if (CarbonImmutable::instance($from)->lessThan($earliest)) {
            return [$earliest, $to];
        }

        return [$from, $to];
    }
}
