<?php

namespace mmeyer2k\PdoEnforcer;

use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Mail\Message;
use Illuminate\Support\ServiceProvider;

class PdoEnforcerServiceProvider extends ServiceProvider
{
    private const needles = [
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
            $allowUnsafe = config(PdoEnforcer::isUnsafeAllowed());

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
            $query = PdoEnforcer::normalizeQuery($event->statement->queryString ?? '');

            // Skip the rest of this function if allowWhen returns true
            if ($this->allowWhen($query)) {
                return;
            }

            // Handle queries containing potentially dangerous things
            if (\Str::contains($query, self::needles)) {
                // Run the customizable event
                $this->throwError($query);
            }
        });
    }

    public function allowWhen(string $query): bool {
        // When this function returns TRUE the query will bypass the parameter checking
        // Returning FALSE (default) will cause the check to be done
        return false;
    }

    public function throwError(string $query)
    {
        // Throw exception back to PDO which will become Illuminate\Database\QueryException
        throw new \Exception("Query contains an invalid character sequence");
    }
}
