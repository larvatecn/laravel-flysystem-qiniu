<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */

namespace Larva\Flysystem\Qiniu\Tests;

use Carbon\Carbon;
use Larva\Flysystem\Qiniu\QiniuAdapter;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use PHPUnit\Framework\TestCase;
use Qiniu\Auth;

/**
 * Class AdapterTest
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class QiniuAdapterTest extends TestCase
{
    public function Provider()
    {
        $config = [
            'access_key' => getenv('QINIU_ACCESS_KEY'),
            'secret_key' => getenv('QINIU_SECRET_KEY'),
            'region' => getenv('QINIU_RQGION'),
            'bucket' => getenv('QINIU_BUCKET'),
            'prefix' => getenv('QINIU_PREFIX'), // optional
            'url' => getenv('QINIU_BUCKET_URL'),
            'visibility' => 'private',
        ];
        $auth = new Auth($config['access_key'], $config['secret_key']);
        $adapter = new QiniuAdapter($auth, $config);

        $options = [
            'machineId' => PHP_OS . PHP_VERSION,
        ];

        return [
            [$adapter, $config, $options],
        ];
    }

    /**
     * @dataProvider Provider
     */
    public function testWrite(AdapterInterface $adapter, $config, $options)
    {
        $this->assertTrue((bool)$adapter->write(
            "foo/{$options['machineId']}/foo.md",
            'content',
            new Config()
        ));
    }

    /**
     * @dataProvider Provider
     */
    public function testWriteStream(AdapterInterface $adapter, $config, $options)
    {
        $temp = tmpfile();
        fwrite($temp, 'writing to tempfile');
        $this->assertTrue((bool)$adapter->writeStream(
            "foo/{$options['machineId']}/bar.md",
            $temp,
            new Config()
        ));
        fclose($temp);
    }

    /**
     * @dataProvider Provider
     */
    public function testUpdate(AdapterInterface $adapter, $config, $options)
    {
        $this->assertTrue((bool)$adapter->update(
            "foo/{$options['machineId']}/foo.md",
            uniqid(),
            new Config()
        ));
    }

    /**
     * @dataProvider Provider
     */
    public function testUpdateStream(AdapterInterface $adapter, $config, $options)
    {
        $temp = tmpfile();
        fwrite($temp, 'writing to tempfile');
        $this->assertTrue((bool)$adapter->updateStream(
            "foo/{$options['machineId']}/bar.md",
            $temp,
            new Config()
        ));
        fclose($temp);
    }

    /**
     * @dataProvider Provider
     */
    public function testCopy(AdapterInterface $adapter, $config, $options)
    {
        $this->assertTrue($adapter->copy(
            "foo/{$options['machineId']}/foo.md",
            "foo/{$options['machineId']}/copy.md"
        ));
    }

    /**
     * @dataProvider Provider
     */
    public function testRename(AdapterInterface $adapter, $config, $options)
    {
        $this->assertTrue($adapter->rename(
            "foo/{$options['machineId']}/foo.md",
            "/foo/{$options['machineId']}/rename.md"
        ));
    }

    /**
     * @dataProvider Provider
     */
    public function testDelete(AdapterInterface $adapter, $config, $options)
    {
        $this->assertTrue($adapter->delete("foo/{$options['machineId']}/rename.md"));
    }

    /**
     * @dataProvider Provider
     */
    public function testCreateDir(AdapterInterface $adapter, $config, $options)
    {
        $this->assertTrue((bool)$adapter->createDir(
            "bar/{$options['machineId']}", new Config()
        ));
    }

    /**
     * @dataProvider Provider
     */
    public function testDeleteDir(AdapterInterface $adapter, $config, $options)
    {
        $this->assertTrue($adapter->deleteDir("bar/{$options['machineId']}"));
    }

    /**
     * @dataProvider Provider
     */
    public function testSetVisibility(AdapterInterface $adapter, $config, $options)
    {
        $this->assertArrayHasKey('size', $adapter->setVisibility(
            "foo/{$options['machineId']}/copy.md", 'private'
        ));
    }

    /**
     * @dataProvider Provider
     */
    public function testHas(AdapterInterface $adapter, $config, $options)
    {
        $this->assertTrue($adapter->has("foo/{$options['machineId']}/bar.md"));
    }

    /**
     * @dataProvider Provider
     */
    public function testRead(AdapterInterface $adapter, $config, $options)
    {
        $this->assertArrayHasKey(
            'contents',
            $adapter->read("foo/{$options['machineId']}/bar.md")
        );

        $this->assertSame(
            file_get_contents($adapter->getTemporaryUrl(
                "foo/{$options['machineId']}/bar.md", Carbon::now()->addMinutes(5)
            )),
            $adapter->read("foo/{$options['machineId']}/bar.md")['contents']
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testGetUrl(AdapterInterface $adapter, $config, $options)
    {
        $this->assertStringContainsString(
            "foo/{$options['machineId']}/bar.md",
            $adapter->getUrl("foo/{$options['machineId']}/bar.md")
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testReadStream(AdapterInterface $adapter, $config, $options)
    {
        $this->assertArrayHasKey(
            'stream',
            $adapter->readStream("foo/{$options['machineId']}/bar.md")
        );

        $this->assertSame(
            stream_get_contents(fopen($adapter->getTemporaryUrl(
                "foo/{$options['machineId']}/bar.md", Carbon::now()->addMinutes(5)
            ), 'rb', false)),
            stream_get_contents($adapter->readStream(
                "foo/{$options['machineId']}/bar.md")['stream']
            )
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testListContents(AdapterInterface $adapter, $config, $options)
    {
        $this->assertArrayHasKey(
            0,
            $adapter->listContents("foo/{$options['machineId']}")
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testGetMetadata(AdapterInterface $adapter, $config, $options)
    {
        $this->assertArrayHasKey(
            'size',
            $adapter->getMetadata("foo/{$options['machineId']}/bar.md")
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testGetSize(AdapterInterface $adapter, $config, $options)
    {
        $this->assertArrayHasKey(
            'size',
            $adapter->getSize("foo/{$options['machineId']}/bar.md")
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testGetMimetype(AdapterInterface $adapter, $config, $options)
    {
        $this->assertNotSame(
            ['mimetype' => ''],
            $adapter->getMimetype("foo/{$options['machineId']}/bar.md")
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testGetTimestamp(AdapterInterface $adapter, $config, $options)
    {
        $this->assertNotSame(
            ['timestamp' => 0],
            $adapter->getTimestamp("foo/{$options['machineId']}/bar.md")
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testGetVisibility(AdapterInterface $adapter, $config, $options)
    {
        $this->assertSame(
            ['visibility' => 'private'],
            $adapter->getVisibility("foo/{$options['machineId']}/copy.md")
        );
    }
}
