<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class Timezone
{
    public static function toLocal(?CarbonInterface $dateTime, ?string $timezone = null): ?CarbonImmutable
    {
        if (! $dateTime) {
            return null;
        }

        $timezone ??= config('app.timezone', 'America/Maceio');

        return CarbonImmutable::instance($dateTime)->setTimezone($timezone);
    }

    public static function toUtcFromLocal(?string $dateTime, ?string $timezone = null): ?CarbonImmutable
    {
        if (! $dateTime) {
            return null;
        }

        $timezone ??= config('app.timezone', 'America/Maceio');

        return CarbonImmutable::parse($dateTime, $timezone)->setTimezone('UTC');
    }

    public static function formatLocal(?CarbonInterface $dateTime, string $format = 'c', ?string $timezone = null): ?string
    {
        return static::toLocal($dateTime, $timezone)?->format($format);
    }
}
