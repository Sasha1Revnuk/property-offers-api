<?php

namespace Tests\Unit\Support;

use App\Support\ApiDateTimeFormatter;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiDateTimeFormatterTest extends TestCase
{
    #[Test]
    public function format_returns_null_for_null_input(): void
    {
        $this->assertNull(ApiDateTimeFormatter::format(null));
    }

    #[Test]
    public function format_returns_iso8601_utc_with_z(): void
    {
        $dateTime = CarbonImmutable::parse('2026-09-03 17:07:00', 'Europe/Kyiv');

        $this->assertSame(
            '2026-09-03T14:07:00Z',
            ApiDateTimeFormatter::format($dateTime),
        );
    }

    #[Test]
    public function format_keeps_already_utc_values(): void
    {
        $dateTime = CarbonImmutable::parse('2026-09-10T23:59:59Z');

        $this->assertSame(
            '2026-09-10T23:59:59Z',
            ApiDateTimeFormatter::format($dateTime),
        );
    }
}
