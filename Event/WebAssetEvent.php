<?php

namespace Msgframework\Lib\Event;

use Msgframework\Lib\AssetManager\WebAssetItemInterface;
use Msgframework\Lib\AssetManager\WebAssetRegistryInterface;

class WebAssetEvent extends AbstractEvent
{
    private WebAssetRegistryInterface $registry;
    private WebAssetItemInterface $asset;
    private string $type;
    private string $action;

    /**
     * @param WebAssetRegistryInterface $webAssetRegistry
     * @param WebAssetItemInterface $asset
     * @param string $assetType
     * @param string $action
     */
    public function __construct(WebAssetRegistryInterface $webAssetRegistry, WebAssetItemInterface $asset, string $assetType, string $action)
    {
        $this->registry = $webAssetRegistry;
        $this->asset = $asset;
        $this->type = $assetType;
        $this->action = $action;
    }

    /**
     * Returns the Web Asset Registry in which this event was thrown.
     *
     * @return WebAssetRegistryInterface
     */
    public function getRegistry(): WebAssetRegistryInterface
    {
        return $this->registry;
    }

    /**
     * Returns asset is currently processing.
     *
     * @return WebAssetItemInterface
     */
    public function getAsset(): WebAssetItemInterface
    {
        return $this->asset;
    }

    /**
     * Returns the asset type the event is currently processing.
     *
     * @return string
     *
     */
    public function getAssetType(): string
    {
        return $this->type;
    }

    /**
     * Returns action.
     *
     * @return string
     *
     */
    public function getAction(): string
    {
        return $this->action;
    }
}