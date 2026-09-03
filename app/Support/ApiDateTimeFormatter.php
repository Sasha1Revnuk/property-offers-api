<?php

namespace App\Support;

use Carbon\CarbonInterface;

final class ApiDateTimeFormatter
{
    public static function format(?CarbonInterface $dateTime): ?string
    {
        if ($dateTime === null) {
            return null;
        }

        return $dateTime->clone()->utc()->format('Y-m-d\TH:i:s\Z');
    }
}
