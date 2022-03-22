<?php

namespace Msgframework\Lib\AssetManager\AssetItem;

use Msgframework\Lib\AssetManager\WebAssetAttachBehaviorInterface;
use Msgframework\Lib\AssetManager\WebAssetItem;

/**
 * Web Asset Item class for Core asset
 */
class CoreAssetItem extends WebAssetItem implements WebAssetAttachBehaviorInterface
{
    /**
     * Method called when asset attached to the Document.
     * Useful for Asset to add a Script options.
     *
     * @return void
     */
	public function onAttachCallback()
	{
	}
}
