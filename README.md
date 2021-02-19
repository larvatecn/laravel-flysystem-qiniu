# laravel-flysystem-qiniu

This is a Flysystem adapter for the Qiniu

[![Build Status](https://travis-ci.com/larvatech/laravel-flysystem-qiniu.svg?branch=master)](https://travis-ci.com/larvatech/laravel-flysystem-qiniu)


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
    'region' => env('QINIU_RQGION'),
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
