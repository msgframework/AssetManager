<?php


use Msgframework\Lib\AssetManager\Exception\InvalidActionException;
use Msgframework\Lib\AssetManager\WebAssetManager;
use Msgframework\Lib\AssetManager\WebAssetManagerInterface;
use Msgframework\Lib\AssetManager\WebAssetRegistry;
use PHPUnit\Framework\TestCase;

class WebAssetManagerTest extends TestCase
{

    public function testCreate(): WebAssetManagerInterface
    {
        $registry = new WebAssetRegistry(__DIR__);
        // Add Core registry files
        $registry
            ->addRegistryFile('asset/media.asset.json');

        $manager = new WebAssetManager($registry);

        $this->assertEquals($registry, $manager->getRegistry());

        return $manager;
    }

    public function testGetAssetState()
    {
        $registry = new WebAssetRegistry(__DIR__);
        // Add Core registry files
        $registry
            ->addRegistryFile('asset/media.asset.json');

        $manager = new WebAssetManager($registry);

        $manager->useAsset('style', 'test.styles2');
        $this->assertEquals(1, $manager->getAssetState('style', 'test.styles2'));
    }

    /**
     * @depends  testCreate
     */
    public function testGetRegistry(WebAssetManagerInterface $manager)
    {
        $this->assertInstanceOf(WebAssetRegistry::class, $manager->getRegistry());
    }

    public function testAddInline()
    {
        $registry = new WebAssetRegistry(__DIR__);
        // Add Core registry files
        $registry
            ->addRegistryFile('asset/media.asset.json');

        $manager = new WebAssetManager($registry);
        $manager->addInline('style', 'body{color: black;}', array(), array(), array('test.styles2'));

        $this->assertCount(2, $manager->getAssets('style'));
    }

    public function testRegisterAsset()
    {
        $registry = new WebAssetRegistry(__DIR__);
        // Add Core registry files
        $registry
            ->addRegistryFile('asset/media.asset.json');

        $manager = new WebAssetManager($registry);

        $asset = new \Msgframework\Lib\AssetManager\WebAssetItem('test.styles4', 'css/test5.min.css', array('type' => 'style'));

        $manager->registerAsset('style', $asset);

        $this->assertEquals($asset, $manager->getAsset('style', 'test.styles4'));
    }

    public function testUseAsset()
    {
        $registry = new WebAssetRegistry(__DIR__);
        // Add Core registry files
        $registry
            ->addRegistryFile('asset/media.asset.json');

        $manager = new WebAssetManager($registry);
        $manager->useAsset('style', 'test.styles1');

        $this->assertEquals(1, $manager->getAssetState('style', 'test.styles1'));
    }

    /**
     * @depends  testCreate
     */
    public function testAssetExists(WebAssetManagerInterface $manager)
    {
        $this->assertTrue($manager->assetExists('style', 'test.styles1'));
    }

    public function testFilterOutInlineAssets()
    {
        $registry = new WebAssetRegistry(__DIR__);
        // Add Core registry files
        $registry
            ->addRegistryFile('asset/media.asset.json');

        $manager = new WebAssetManager($registry);

        $manager->addInline('style', 'body{color: black;}', array(), array(), array('test.styles2'));

        $assets = $manager->getAssets('style');

        $this->assertCount(1, $manager->filterOutInlineAssets($assets));
    }

    public function testIsAssetActive()
    {
        $registry = new WebAssetRegistry(__DIR__);
        // Add Core registry files
        $registry
            ->addRegistryFile('asset/media.asset.json');

        $manager = new WebAssetManager($registry);

        $manager->useAsset('style', 'test.styles1');

        $this->assertTrue($manager->isAssetActive('style', 'test.styles1'));
        $this->assertFalse($manager->isAssetActive('style', 'test.styles2'));
    }

    public function testGetInlineRelation()
    {
        $registry = new WebAssetRegistry(__DIR__);
        // Add Core registry files
        $registry
            ->addRegistryFile('asset/media.asset.json');

        $manager = new WebAssetManager($registry);

        $manager->addInline('style', 'body{color: black;}', array('type' => 'style', 'inline' => true, 'position' => 'after'), array(), array('test.styles2'));

        $this->assertEquals('test.styles2', array_key_first($manager->getInlineRelation($manager->getAssets('style'))));
    }

    public function testGetManagerState()
    {
        $registry = new WebAssetRegistry(__DIR__);
        // Add Core registry files
        $registry
            ->addRegistryFile('asset/media.asset.json');

        $manager = new WebAssetManager($registry);

        $state1 = $manager->getManagerState();

        $this->assertEquals(array(), $state1['activeAssets']);

        $manager->useAsset('style', 'test.styles1');

        $state2 = $manager->getManagerState();

        $this->assertEquals(array('style' => array('test.styles1' => 1)), $state2['activeAssets']);
    }

    public function testDisableAsset()
    {
        $registry = new WebAssetRegistry(__DIR__);
        // Add Core registry files
        $registry
            ->addRegistryFile('asset/media.asset.json');

        $manager = new WebAssetManager($registry);

        $manager->useAsset('style', 'test.styles1');

        $state1 = $manager->getManagerState();

        $this->assertEquals(array('style' => array('test.styles1' => 1)), $state1['activeAssets']);

        $manager->disableAsset('style', 'test.styles1');

        $state2 = $manager->getManagerState();

        $this->assertEquals(array('style' => array()), $state2['activeAssets']);
    }

    public function testGetAsset()
    {
        $registry = new WebAssetRegistry(__DIR__);
        // Add Core registry files
        $registry
            ->addRegistryFile('asset/media.asset.json');

        $manager = new WebAssetManager($registry);

        $asset = $manager->getAsset('style', 'test.styles2');

        $this->assertInstanceOf(\Msgframework\Lib\AssetManager\WebAssetItemInterface::class, $asset);
    }

    public function testGetAssets()
    {
        $registry = new WebAssetRegistry(__DIR__);
        // Add Core registry files
        $registry
            ->addRegistryFile('asset/media.asset.json');

        $manager = new WebAssetManager($registry);

        $manager->useAsset('style', 'test.depend');
        $manager->useAsset('style', 'test.styles2');
        $manager->useAsset('style', 'test.styles1');

        $assets1 = $manager->getAssets('style', false);
        $assets2 = $manager->getAssets('style', true);

        $this->assertEquals('test.depend', array_key_first($assets1));
        $this->assertEquals('test.styles2', array_key_first($assets2));
    }

    /**
     * @depends  testCreate
     */
    public function testLock(WebAssetManagerInterface $manager)
    {
        $manager->lock();

        $this->expectException(InvalidActionException::class);

        $manager->useAsset('style', 'test.styles2');
    }
}
