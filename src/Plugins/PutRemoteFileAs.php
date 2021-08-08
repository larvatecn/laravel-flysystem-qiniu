<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */

namespace Larva\Flysystem\Qiniu\Plugins;

use League\Flysystem\Plugin\AbstractPlugin;

class PutRemoteFileAs extends AbstractPlugin
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return 'putRemoteFileAs';
    }

    /**
     * 另存远程文件
     * @param string $path
     * @param string $remoteUrl
     * @param string $name
     * @param array $options
     * @return array
     */
    public function handle(string $path, string $remoteUrl, string $name, array $options = [])
    {
        $path = trim($path . '/' . $name, '/');
        return $this->filesystem->getAdapter()->getBucketManager()->fetch($remoteUrl, $this->filesystem->getAdapter()->getBucket(), $path);
    }
}
