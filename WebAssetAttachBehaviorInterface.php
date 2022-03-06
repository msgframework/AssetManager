<?php

namespace Msgframework\Lib\AssetManager;

use RocketCMS\Lib\Document\Document;

/**
 * Web Asset Behavior interface
 */
interface WebAssetAttachBehaviorInterface
{
	/**
	 * Method called when asset attached to the Document.
	 * Useful for Asset to add a Script options.
	 *
	 * @param   Document  $doc  Active document
	 *
	 * @return void
	 */
	public function onAttachCallback(Document $doc);
}
