<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;

/**
 * A DATETIME column holding server wall-clock time (config('app.timezone')).
 * Server code keeps reading and writing the raw "Y-m-d H:i:s" value untouched; only JSON output gains the
 * server's UTC offset (ISO 8601), so the browser can convert to the user's local time without having to know
 * or agree with the server's timezone rules.
 */
class ServerDateTime implements CastsAttributes, SerializesCastableAttributes {
    public function get($model, string $key, $value, array $attributes) {
        return $value;
    }

    public function set($model, string $key, $value, array $attributes) {
        return $value;
    }

    public function serialize($model, string $key, $value, array $attributes) {
        return self::toIso($value);
    }

    /**
     * Converts a server wall-clock datetime to ISO 8601 with the server's offset. Anything else (null, date-only,
     * already offset or unparseable values) is returned unchanged.
     */
    public static function toIso($value) {
        if(!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $value))
            return $value;

        return (new \DateTime($value, new \DateTimeZone(config('app.timezone'))))->format(\DateTime::ATOM);
    }
}
