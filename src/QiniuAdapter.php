<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */

namespace Larva\Flysystem\Qiniu;

use Carbon\Carbon;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\StreamedTrait;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\Util;
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;

/**
 * Qiniu适配器
 * @package XuTL\Flysystem\Qiniu
 */
class QiniuAdapter extends AbstractAdapter
{
    use StreamedTrait;

    /**
     * @var BucketManager
     */
    private BucketManager $bucketManager;

    /**
     * @var UploadManager
     */
    private UploadManager $uploadManager;

    /**
     * @var Auth
     */
    private Auth $auth;

    /**
     * @var array
     */
    protected array $config = [];

    /**
     * Constructor.
     *
     * @param Auth $auth
     * @param array $config
     */
    public function __construct(Auth $auth, array $config)
    {
        $this->auth = $auth;
        $this->config = $config;
        $this->bucketManager = new BucketManager($this->auth);
        $this->uploadManager = new UploadManager();
        $this->setPathPrefix($config['prefix']);
    }

    /**
     * @return BucketManager
     */
    public function getBucketManager(): BucketManager
    {
        return $this->bucketManager;
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $config)
    {
        $object = $this->applyPathPrefix($path);
        $token = $this->auth->uploadToken($this->getBucket(), $object);
        $params = $config->get('params', null);
        $checkCrc = $config->get('checkCrc', false);
        $mime = Util::guessMimeType($path, $contents);
        [, $error] = $this->uploadManager->put($token, $object, $contents, $params, $mime, $checkCrc);
        if ($error !== null) {
            return false;
        } else {
            $type = 'file';
            $result = compact('type', 'path', 'contents');
            $result['mimetype'] = $mime;
            $result['size'] = Util::contentSize($contents);
            return $result;
        }
    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     * @return array|false false on failure file meta data on success
     */
    public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     * @return bool
     */
    public function rename($path, $newpath): bool
    {
        if (!$this->copy($path, $newpath)) {
            return false;
        }
        return $this->delete($path);
    }

    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     * @return bool
     */
    public function copy($path, $newpath): bool
    {
        $object = ltrim($this->applyPathPrefix($path), '/');
        $newObject = ltrim($this->applyPathPrefix($newpath), '/');
        [, $error] = $this->bucketManager->copy($this->getBucket(), $object, $this->getBucket(), $newObject, true);
        if ($error != null) {
            return false;
        }
        return true;
    }

    /**
     * Delete a file.
     *
     * @param string $path
     * @return bool
     */
    public function delete($path): bool
    {
        $object = $this->applyPathPrefix($path);
        [, $error] = $this->bucketManager->delete($this->getBucket(), $object);
        if ($error != null) {
            return false;
        }
        return true;
    }

    /**
     * Read a file.
     *
     * @param string $path
     * @return array|false
     */
    public function read($path)
    {
        if ($this->config['visibility'] == AdapterInterface::VISIBILITY_PRIVATE) {
            $url = $this->getTemporaryUrl($path, Carbon::now()->addMinutes(5));
        } else {
            $url = $this->getUrl($path);
        }
        $contents = file_get_contents($url);
        return compact('contents', 'path');
    }

    /**
     * 获取对象访问Url
     * @param string $path
     * @return string
     */
    public function getUrl($path)
    {
        if ($this->config['visibility'] == AdapterInterface::VISIBILITY_PRIVATE) {
            return $this->getTemporaryUrl($path, Carbon::now()->addMinutes(5));
        }
        $location = $this->applyPathPrefix($path);
        return $this->config['url'] . '/' . ltrim($location, '/');
    }

    /**
     * 获取文件临时访问路径
     * @param $path
     * @param $expiration
     * @param $options
     * @return string
     */
    public function getTemporaryUrl($path, \DateTimeInterface $expiration, array $options = [])
    {
        if ($this->config['visibility'] == AdapterInterface::VISIBILITY_PUBLIC) {
            return $this->getUrl($path);
        }
        $location = $this->applyPathPrefix($path);
        $timeout = $expiration->getTimestamp() - time();
        return $this->auth->privateDownloadUrl($this->config['url'] . '/' . ltrim($location, '/'), $timeout);
    }

    /**
     * Delete a directory.
     *
     * @param string $dirname
     * @return bool
     */
    public function deleteDir($dirname): bool
    {
        $files = $this->listContents($dirname);
        foreach ($files as $file) {
            $this->delete($file['path']);
        }
        return true;
    }

    /**
     * Create a directory.
     *
     * @param string $dirname directory name
     * @param Config $config
     * @return array
     */
    public function createDir($dirname, Config $config): array
    {
        $object = $this->applyPathPrefix($dirname);
        return ['path' => $object, 'type' => 'dir'];
    }

    /**
     * Set the visibility for a file.
     *
     * @param string $path
     * @param string $visibility
     * @return array|false file meta data
     */
    public function setVisibility($path, $visibility)
    {
        $meta = $this->getMetadata($path);
        if ($meta) {
            if ($this->config['visibility'] == AdapterInterface::VISIBILITY_PRIVATE && $visibility == AdapterInterface::VISIBILITY_PRIVATE) {
                return $meta;
            } else if ($this->config['visibility'] == AdapterInterface::VISIBILITY_PUBLIC && $visibility == AdapterInterface::VISIBILITY_PUBLIC) {
                return $meta;
            }
        }
        return false;
    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     * @return bool
     */
    public function has($path): bool
    {
        $meta = $this->getMetadata($path);
        if ($meta) {
            return true;
        }
        return false;
    }

    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool $recursive
     * @return array
     */
    public function listContents($directory = '', $recursive = false): array
    {
        $directory = $this->applyPathPrefix($directory);
        $list = [];
        $marker = null;
        while (true) {
            $response = $this->listObjects($directory, $recursive, $marker);
            foreach ($response['items'] as $content) {
                $list[] = $this->normalizeFileInfo($content);
            }
            if (empty($response['marker'])) {
                break;
            }
            $marker = $response['marker'] ?: '';
        }
        return $list;
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     * @return array|false
     */
    public function getMetadata($path)
    {
        $object = $this->applyPathPrefix($path);
        [$ret, $error] = $this->bucketManager->stat($this->getBucket(), $object);
        if ($error !== null) {
            return false;
        } else {
            return [
                'type' => 'file',
                'dirname' => dirname($path),
                'path' => $path,
                'timestamp' => sprintf("%d", $ret['putTime'] * 100 / 1e9),
                'mimetype' => $ret['mimeType'],
                'size' => $ret['fsize'],
            ];
        }
    }

    /**
     * Get the size of a file.
     *
     * @param string $path
     * @return array|false
     */
    public function getSize($path)
    {
        $meta = $this->getMetadata($path);
        return isset($meta['size'])
            ? ['size' => $meta['size']] : false;
    }

    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     * @return array|false
     */
    public function getMimetype($path)
    {
        $meta = $this->getMetadata($path);
        return isset($meta['mimetype'])
            ? ['mimetype' => $meta['mimetype']] : false;
    }

    /**
     * Get the last modified time of a file as a timestamp.
     *
     * @param string $path
     * @return array|false
     */
    public function getTimestamp($path)
    {
        $meta = $this->getMetadata($path);
        return isset($meta['timestamp'])
            ? ['timestamp' => strtotime($meta['timestamp'])] : false;
    }

    /**
     * @inheritDoc
     */
    public function getVisibility($path)
    {
        $meta = $this->getMetadata($path);
        if ($meta) {
            if ($this->config['visibility'] == AdapterInterface::VISIBILITY_PRIVATE) {
                return ['visibility' => AdapterInterface::VISIBILITY_PRIVATE];
            } else {
                return ['visibility' => AdapterInterface::VISIBILITY_PUBLIC];
            }
        }
        return false;
    }

    /**
     * List objects of a directory.
     * @param string $directory
     * @param bool $recursive
     * @param string $marker max return 1000 record, if record greater than 1000
     *                          you should set the next marker to get the full list
     *
     * @return array
     */
    private function listObjects(string $directory = '', bool $recursive = false, $marker = null): array
    {
        $prefix = $directory === '' ? '' : ($directory . '/');
        $delimiter = $recursive ? '' : '/';
        list($res, $error) = $this->bucketManager->listFiles($this->getBucket(), $prefix, $marker, 2, $delimiter);
        if ($error !== null) {
            return [
                'items' => [],
                'marker' => '',
            ];
        } else {
            return [
                'items' => $res['items'],
                'marker' => isset($res['marker']) ? $res['marker'] : '',
            ];
        }
    }

    /**
     * @param array $content
     *
     * @return array
     */
    private function normalizeFileInfo(array $content): array
    {
        $path = pathinfo($content['key']);
        return [
            'type' => 'file',
            'path' => $content['key'],
            'timestamp' => sprintf("%d", $content['putTime'] * 100 / 1e9),
            'size' => $content['fsize'],
            'dirname' => $path['dirname'] === '.' ? '' : (string)$path['dirname'],
            'basename' => (string)$path['basename'],
            'extension' => isset($path['extension']) ? $path['extension'] : '',
            'filename' => (string)$path['filename'],
        ];
    }

    /**
     * Get the Qiniu bucket.
     *
     * @return string
     */
    public function getBucket(): string
    {
        return $this->config['bucket'];
    }
}
