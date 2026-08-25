<?php

namespace SmartDato\CblLogistica\Resources;

use DateTimeInterface;
use SmartDato\CblLogistica\Data\Responses\StatusResultData;
use SmartDato\CblLogistica\Requests\Status\RequestStatusByDateRangeRequest;
use SmartDato\CblLogistica\Requests\Status\RequestStatusByReferenceRequest;
use SmartDato\CblLogistica\Support\ClampsDateWindows;

class StatusResource extends BaseResource
{
    use ClampsDateWindows;

    /**
     * The widest window CBL serves. A wider request is silently clamped to
     * dateTo minus this many days, with warning 300 attached.
     */
    public const int MAX_WINDOW_DAYS = 30;

    public function byReference(string $reference): StatusResultData
    {
        $response = $this->send(new RequestStatusByReferenceRequest($reference));

        return StatusResultData::from($response->json());
    }

    public function byDateRange(DateTimeInterface $from, DateTimeInterface $to): StatusResultData
    {
        [$from, $to] = $this->clampWindow($from, $to, self::MAX_WINDOW_DAYS);

        $response = $this->send(new RequestStatusByDateRangeRequest($from, $to));

        return StatusResultData::from($response->json());
    }
}
