<?php

namespace Msgframework\Lib\AssetManager\Tests;

use Msgframework\Lib\AssetManager\WebAssetRegistry;
use Msgframework\Lib\AssetManager\WebAssetRegistryInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test class for ::Registry.
 */
class WebAssetRegistryTest2 extends TestCase
{
    function testWebAssetRegistryCreate() {
        $registry = new WebAssetRegistry(__DIR__);

        $this->assert(WebAssetRegistryInterface, $registry);

        return $registry;
    }

    /**
     * @depends testWebAssetRegistryCreate
     */
    function testAddRegistryFile(WebAssetRegistryInterface $registry) {
        $registry = new WebAssetRegistry(__DIR__);

        // Add Core registry files
        $registry
            ->addRegistryFile('asset/media.asset.json');

        $this->assertEquals(WebAssetRegistryInterface, $registry);
    }
}
