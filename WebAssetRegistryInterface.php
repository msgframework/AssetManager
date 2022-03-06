<?php

namespace Msgframework\Lib\AssetManager;


use Msgframework\Lib\AssetManager\Exception\UnknownAssetException;

/**
 * Web Asset Registry interface
 */
interface WebAssetRegistryInterface
{
    /**
     * Get an existing Asset from a registry, by asset name and asset type.
     *
     * @param string $type Asset type, script or style
     * @param string $name Asset name
     *
     * @return  WebAssetItem
     *
     * @throws  UnknownAssetException  When Asset cannot be found
     */
    public function get(string $type, string $name): WebAssetItemInterface;

    /**
     * Add Asset to registry of known assets
     *
     * @param string $type Asset type, script or style
     * @param WebAssetItemInterface $asset Asset instance
     *
     * @return  self
     */
    public function add(string $type, WebAssetItemInterface $asset): self;

    /**
     * Remove Asset from registry.
     *
     * @param string $type Asset type, script or style etc
     * @param string $name Asset name
     *
     * @return  self
     */
    public function remove(string $type, string $name): self;

    /**
     * Check whether the asset exists in the registry.
     *
     * @param string $type Asset type, script or style etc
     * @param string $name Asset name
     *
     * @return  boolean
     */
    public function exists(string $type, string $name): bool;
}

