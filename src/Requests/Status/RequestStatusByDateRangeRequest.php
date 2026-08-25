<?php

namespace SmartDato\CblLogistica\Requests\Status;

use DateTimeInterface;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use SmartDato\CblLogistica\Support\CblDateFormats;

class RequestStatusByDateRangeRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected readonly DateTimeInterface $dateFrom,
        protected readonly DateTimeInterface $dateTo,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/ShipmentStatus/RequestStatusByDateRange';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return [
            'dateFrom' => $this->dateFrom->format(CblDateFormats::OUTGOING),
            'dateTo' => $this->dateTo->format(CblDateFormats::OUTGOING),
        ];
    }
}
