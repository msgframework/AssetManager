<?php

namespace Msgframework\Lib\AssetManager;

use Msgframework\Lib\Document\Document;

/**
 * Web Asset Behavior interface
 */
interface WebAssetAttachBehaviorInterface
{
    /**
     * Method called when asset attached to the Document.
     * Useful for Asset to add a Script options.
     *
     * @return void
     */
	public function onAttachCallback();
}
