<?php

declare(strict_types=1);

namespace mmeyer2k\PdoEnforcer;

class PdoEnforcer
{
    private const configString = 'sqliquard.allow_unsafe_mysql';

    public static function allowUnsafe(): void
    {
        config([self::configString => true]);
    }

    public static function disallowUnsafe(): void
    {
        config([self::configString => false]);
    }

    public static function isUnsafeAllowed(): bool
    {
        return config(self::configString);
    }

    public static function normalizeQuery(string $sql): string
    {
        $sql = strtolower($sql);

        $sql = preg_replace('/\s+/', ' ', $sql);

        $sql = str_replace(' (', '(', $sql);

        return $sql;
    }
}
