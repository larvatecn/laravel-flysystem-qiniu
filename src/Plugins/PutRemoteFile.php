<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */

namespace Larva\Flysystem\Qiniu\Plugins;

use League\Flysystem\Plugin\AbstractPlugin;

/**
 * Class PutRemoteFile
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class PutRemoteFile extends AbstractPlugin
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return 'putRemoteFile';
    }

    /**
     * 获取远程文件
     * @param string $path
     * @param string $remoteUrl
     * @param array $options
     * @return array
     */
    public function handle(string $path, string $remoteUrl, array $options = [])
    {
        return $this->filesystem->getAdapter()->getBucketManager()->fetch($remoteUrl, $this->filesystem->getAdapter()->getBucket());
    }
}
