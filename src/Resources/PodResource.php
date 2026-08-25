<?php

namespace SmartDato\CblLogistica\Resources;

use DateTimeInterface;
use SmartDato\CblLogistica\Data\Responses\PodResultData;
use SmartDato\CblLogistica\Requests\Pod\RequestPodByDateRangeRequest;
use SmartDato\CblLogistica\Requests\Pod\RequestPodByReferenceRequest;
use SmartDato\CblLogistica\Support\ClampsDateWindows;

class PodResource extends BaseResource
{
    use ClampsDateWindows;

    /**
     * Proof of delivery is served over a narrower window than status.
     */
    public const int MAX_WINDOW_DAYS = 7;

    public function byReference(string $reference): PodResultData
    {
        $response = $this->send(new RequestPodByReferenceRequest($reference));

        return PodResultData::from($response->json());
    }

    public function byDateRange(DateTimeInterface $from, DateTimeInterface $to): PodResultData
    {
        [$from, $to] = $this->clampWindow($from, $to, self::MAX_WINDOW_DAYS);

        $response = $this->send(new RequestPodByDateRangeRequest($from, $to));

        return PodResultData::from($response->json());
    }
}
