# pdo-enforcer

PDO-enforcer is a Laravel service provider that prevents execution of un-parameterized queries.

## Requirements
1. Laravel 5.6 and higher
1. PHP 7.1 and higher

## Install Library
To install with composer:

```bash
composer require mmeyer2k/pdo-enforcer
```

## Register Service Provider
Edit `config/app.php` as follows:

```php
return [
    'providers' => [
        # ...
        \mmeyer2k\PdoEnforcer\PdoEnforcerServiceProvider::class,
        # ...
    ],
];
```

## Customize It
Customization of PdoEnforcer is done by creating a new service provider which `extends` the `PdoEnforcerServiceProvider` class.
Don't forget to update your `config/app.php` to register your extended version.

```php
class YourPdoEnforcer extends \mmeyer2k\PdoEnforcer\PdoEnforcerServiceProvider {

    private $badStrings = [
        '--',
        '0x',
        '#',
        "'",
        '"',
        '/',
    ];

    public function allowWhen(string $query): bool {
        // When this function returns TRUE the query will bypass the parameter checking
        // Returning FALSE (default) will cause the check to be done
        return false;
    }

    public function throwError(string $query)
    {
        // Log sql injection attempt...
        // ...

        // Throw exception back to PDO which will become Illuminate\Database\QueryException
        throw new \Exception("Query contains an invalid character sequence");
    }

}
```