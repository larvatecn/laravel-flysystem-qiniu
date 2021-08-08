<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
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
