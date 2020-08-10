<?php

namespace mmeyer2k\sqliguard;

use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Mail\Message;
use Illuminate\Support\ServiceProvider;

class SqliGuard extends ServiceProvider
{
    private const configString = 'sqliquard.allow_unsafe_mysql';

    private const needles = [
        'information_schema',
        'benchmark(',
        'version(',
        'sleep(',
        '--',
        '0x',
        '#',
        "'",
        '"',
        '/',
    ];

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Prevent SQL injection by listening to the PDO statement creation event
        \Event::listen(StatementPrepared::class, function (StatementPrepared $event) {
            // Lowercase the query and remove extra whitespace
            $allowUnsafe = config(self::configString);

            // If being run from commandline, we are always safe, therefore no need to check
            // This block will be triggered during migrations, for instance
            if (App::runningInConsole() && $allowUnsafe === null) {
                return;
            }

            // If sqliquard is disabled, return here.
            if ($allowUnsafe === true) {
                return;
            }

            // Normalize query string to prevent tomfoolery
            $query = self::normalize($event->statement->queryString ?? '');

            // Handle queries containing potentially dangerous things
            if (\Str::contains($query, self::needles)) {
                // Finally, throw exception back to PDO which will become Illuminate\Database\QueryException
                // To prevent this exception from being thrown, call the SqliMonitor::allowUnsafe() method
                throw new \Exception("Query contains an invalid character sequence");
            }
        });
    }

    public static function allowUnsafe(): void
    {
        config([self::configString => true]);
    }

    public static function disallowUnsafe(): void
    {
        config([self::configString => false]);
    }

    private static function normalize(string $sql): string
    {
        $sql = strtolower($sql);

        $sql = preg_replace('/\s+/', ' ', $sql);

        $sql = str_replace(' (', '(', $sql);

        return $sql;
    }
}
