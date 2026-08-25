<?php

namespace SmartDato\CblLogistica\Data\Responses\Concerns;

use SmartDato\CblLogistica\Data\Responses\ErrorData;
use SmartDato\CblLogistica\Data\Responses\WarningData;

/**
 * CBL reports business failures inside an HTTP 200 response, so every payload
 * carries an errorList and a warningList. Callers decide what a populated list
 * means; the SDK never turns one into an exception.
 */
trait HasErrorEnvelope
{
    /**
     * @return array<int, ErrorData>
     */
    public function errors(): array
    {
        return $this->errorList ?? [];
    }

    /**
     * @return array<int, WarningData>
     */
    public function warnings(): array
    {
        return $this->warningList ?? [];
    }

    public function hasErrors(): bool
    {
        return $this->errors() !== [];
    }

    public function hasWarnings(): bool
    {
        return $this->warnings() !== [];
    }

    /**
     * @return array<int, string>
     */
    public function errorMessages(): array
    {
        return array_values(array_filter(array_map(
            static fn (ErrorData $error): ?string => $error->errorDescription,
            $this->errors(),
        )));
    }

    /**
     * @return array<int, string>
     */
    public function warningMessages(): array
    {
        return array_values(array_filter(array_map(
            static fn (WarningData $warning): ?string => $warning->warningDescription,
            $this->warnings(),
        )));
    }
}
