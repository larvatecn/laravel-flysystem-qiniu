# laravel-flysystem-qiniu

This is a Flysystem adapter for the Qiniu

[![PHP Composer](https://github.com/larvatecn/laravel-flysystem-qiniu/actions/workflows/php.yml/badge.svg)](https://github.com/larvatecn/laravel-flysystem-qiniu/actions/workflows/php.yml)

## Installation

```bash
composer require larva/laravel-flysystem-qiniu -vv
```

## for Laravel

This service provider must be registered.

```php
// config/app.php

'providers' => [
    '...',
    Larva\Flysystem\Qiniu\QiniuServiceProvider::class,
];
```

edit the config file: config/filesystems.php

add config

```php
'qiniu' => [
    'driver'     => 'qiniu',
    'access_key' => env('QINIU_ACCESS_KEY'),
    'secret_key' => env('QINIU_SECRET_KEY'),
    'bucket' => env('QINIU_BUCKET'),
    'prefix' => env('QINIU_PREFIX'), // optional
    'url' => env('QINIU_BUCKET_URL'),
    'visibility' => 'private',
],
```

change default to oss

```php
    'default' => 'qiniu'
```

## Use

see [Laravel wiki](https://laravel.com/docs/5.6/filesystem)
