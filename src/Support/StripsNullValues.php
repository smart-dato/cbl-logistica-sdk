<?php

namespace SmartDato\CblLogistica\Support;

trait StripsNullValues
{
    /**
     * CBL's samples send empty strings for absent optional fields and it rejects
     * unexpected nulls, so nulls are dropped from the payload entirely.
     *
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    protected function withoutNullValues(array $data): array
    {
        $filtered = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            $filtered[$key] = is_array($value) ? $this->withoutNullValues($value) : $value;
        }

        return $filtered;
    }
}
