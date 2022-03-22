<?php

namespace Msgframework\Lib\AssetManager\Tests;

use Msgframework\Lib\AssetManager\WebAssetItemInterface;
use Msgframework\Lib\AssetManager\WebAssetRegistry;
use Msgframework\Lib\AssetManager\WebAssetRegistryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WebAssetRegistryTest extends TestCase
{
    function testCreate(): WebAssetRegistryInterface
    {
        $registry = new WebAssetRegistry(__DIR__);

        $this->assertCount(0, $registry->getRegistryFiles());

        return $registry;
    }

    /**
     * @depends testCreate
     */
    public function testAdd(WebAssetRegistryInterface $registry)
    {
        $registry->add('style', $registry->createAsset('test.styles3', 'css/test3.min.css'));

        $this->assertTrue($registry->exists('style', 'test.styles3'));
    }

    /**
     * @depends testCreate
     */
    public function testCreateAsset(WebAssetRegistryInterface $registry): WebAssetItemInterface
    {
        $asset = $registry->createAsset('test.styles3', 'css/test3.min.css');

        $this->assertEquals('test.styles3', $asset->getName());

        return $asset;
    }

    /**
     * @depends testCreate
     */
    public function testSetDispatcher(WebAssetRegistryInterface $registry): EventDispatcher
    {
        $dispatcher = new EventDispatcher;

        $this->assertEquals($dispatcher, $registry->setDispatcher($dispatcher)->getDispatcher());

        return $dispatcher;
    }

    /**
     * @depends testCreate
     * @depends testSetDispatcher
     */
    public function testGetDispatcher(WebAssetRegistryInterface $registry, EventDispatcherInterface $dispatcher)
    {
        $this->assertEquals($dispatcher, $registry->getDispatcher());
    }

    /**
     * @depends testAddRegistryFile
     */
    public function testExists(WebAssetRegistryInterface $registry)
    {
        $this->assertTrue($registry->exists('style', 'test.styles1'));
    }

    /**
     * @depends testCreate
     */
    public function testAddRegistryFile(WebAssetRegistryInterface $registry): WebAssetRegistryInterface
    {
        // Add Core registry files
        $registry
            ->addRegistryFile('asset/media.asset.json');
        $this->assertCount(1, $registry->getRegistryFiles());

        return $registry;

    }

    /**
     * @depends testAddRegistryFile
     */
    public function testRemove(WebAssetRegistryInterface $registry)
    {
        $this->assertTrue($registry->exists('style', 'test.styles1'));
        $registry->remove('style', 'test.styles1');
        $this->assertFalse($registry->exists('style', 'test.styles1'));
    }

    /**
     * @depends testAddRegistryFile
     */
    public function testGetRegistryFiles(WebAssetRegistryInterface $registry)
    {
        $this->assertCount(1, $registry->getRegistryFiles());
    }

    /**
     * @depends testCreate
     * @depends testCreateAsset
     */
    public function testGet(WebAssetRegistryInterface $registry, WebAssetItemInterface $asset)
    {
        $this->assertEquals($asset, $registry->get('style', 'test.styles3'));
    }
}
