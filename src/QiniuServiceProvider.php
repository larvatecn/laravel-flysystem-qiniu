<?php
/**
 * @copyright Copyright (c) 2018 Larva Information Technology Co., Ltd.
 * @link http://www.larvacent.com/
 * @license http://www.larvacent.com/license/
 */

namespace Larva\Flysystem\Qiniu;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use Larva\Flysystem\Qiniu\Plugins\PutRemoteFile;
use Larva\Flysystem\Qiniu\Plugins\PutRemoteFileAs;
use League\Flysystem\Filesystem;
use Qiniu\Auth;

/**
 * Qiniu服务提供者
 * @package XuTL\Flysystem\Qiniu
 */
class QiniuServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function boot()
    {
        $this->app->make('filesystem')->extend('qiniu', function ($app, $config) {
            $auth = new Auth($config['access_key'], $config['secret_key']);
            $flysystem = new Filesystem(new QiniuAdapter($auth, $config), $config);
            $flysystem->addPlugin(new PutRemoteFile());
            $flysystem->addPlugin(new PutRemoteFileAs());
            return $flysystem;
        });
    }


    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
