# Laravel Redis Sorted Set Paginator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-redis-paginator.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-redis-paginator)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/spatie/laravel-redis-paginator/run-tests?label=tests)](https://github.com/spatie/laravel-redis-paginator/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-redis-paginator.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-redis-paginator)


Create a Laravel `LengthAwarePaginato` from a Redis sorted set.

## Installation

You can install the package via composer:

```bash
composer require ge-tracker/laravel-redis-paginator
```

## Usage

``` php
$laravel-redis-paginator = new GeTracker\LaravelRedisPaginator();
echo $laravel-redis-paginator->echoPhrase('Hello, Ge-tracker!');
```

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Credits

- [James Austen](https://github.com/gtjamesa)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
