<?php

namespace Msgframework\Lib\AssetManager\Tests;

use Msgframework\Lib\AssetManager\WebAssetItem;
use Msgframework\Lib\AssetManager\WebAssetItemInterface;
use PHPUnit\Framework\TestCase;

class WebAssetItemTest extends TestCase
{
    public function testCreate(): WebAssetItemInterface
    {
        $asset = new WebAssetItem('test.styles4', 'asset/css/test5.min.css', array('type' => 'style', 'version' => 'auto'), array('rel' => 'lazy-stylesheet'));

        $this->assertInstanceOf(WebAssetItemInterface::class, $asset);
        return $asset;
    }

    /**
     * @depends  testCreate
     */
    public function testGetAttribute(WebAssetItemInterface $asset)
    {
        $this->assertEquals('lazy-stylesheet', $asset->getAttribute('rel'));
    }

    /**
     * @depends  testCreate
     */
    public function testGetAttributes(WebAssetItemInterface $asset)
    {
        $this->assertEquals(array('rel' => 'lazy-stylesheet'), $asset->getAttributes());
    }

    /**
     * @depends  testCreate
     */
    public function testGetUri(WebAssetItemInterface $asset)
    {
        $this->assertEquals('asset/css/test5.min.css', $asset->getUri());
    }

    /**
     * @depends  testCreate
     */
    public function testGetName(WebAssetItemInterface $asset)
    {
        $this->assertEquals('test.styles4', $asset->getName());
    }

    /**
     * @depends  testCreate
     */
    public function testGetOptions(WebAssetItemInterface $asset)
    {
        $this->assertEquals(array('type' => 'style'), $asset->getOptions());
    }

    /**
     * @depends  testCreate
     */
    public function testGetOption(WebAssetItemInterface $asset)
    {
        $this->assertEquals('style', $asset->getOption('type'));
    }

    /**
     * @depends  testCreate
     */
    public function testGetVersion(WebAssetItemInterface $asset)
    {
        $this->assertEquals('auto', $asset->getVersion());
    }

    public function testGetDependencies()
    {
        $asset = new WebAssetItem('test.styles4', 'asset/css/test5.min.css', array('type' => 'style', 'version' => 'auto'), array('rel' => 'lazy-stylesheet'), array('test.styles1'));

        $this->assertEquals(array('test.styles1'), $asset->getDependencies());
    }

    public function testSetOption()
    {
        $asset = new WebAssetItem('test.styles4', 'asset/css/test5.min.css', array('type' => 'style', 'version' => 'auto'), array('rel' => 'lazy-stylesheet'), array('test.styles1'));

        $asset->setOption('version', '1');
        $this->assertEquals(array('type' => 'style'), $asset->getOptions());
        $this->assertEquals(1, $asset->getVersion());

        $asset->setOption('position', '2');
        $this->assertEquals(array('type' => 'style', 'position' => '2'), $asset->getOptions());
    }

    public function testSetOptions()
    {
        $asset = new WebAssetItem('test.styles4', 'asset/css/test5.min.css', array('type' => 'style', 'version' => 'auto'), array('rel' => 'lazy-stylesheet'), array('test.styles1'));

        $asset->setOptions(array('version' => '3'));
        $this->assertEquals(array('type' => 'style'), $asset->getOptions());
        $this->assertEquals(3, $asset->getVersion());
    }

    public function testSetVersion()
    {
        $asset = new WebAssetItem('test.styles4', 'asset/css/test5.min.css', array('type' => 'style', 'version' => 'auto'), array('rel' => 'lazy-stylesheet'), array('test.styles1'));

        $this->assertEquals('auto', $asset->getVersion());
        $asset->setVersion('safasf');
        $this->assertEquals('safasf', $asset->getVersion());
    }

    public function testSetAttribute()
    {
        $asset = new WebAssetItem('test.styles4', 'asset/css/test5.min.css', array('type' => 'style', 'version' => 'auto'), array('rel' => 'lazy-stylesheet'), array('test.styles1'));

        $this->assertEquals('lazy-stylesheet', $asset->getAttribute('rel'));
        $asset->setAttribute('rel', 'lazy-stylesheet2');
        $this->assertEquals('lazy-stylesheet2', $asset->getAttribute('rel'));
    }
}
