<?php

namespace Msgframework\Lib\AssetManager\AssetItem;

use RocketCMS\Lib\Document\Document;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
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
	 * @param   Document  $doc  Active document
	 *
	 * @return void
	 */
	public function onAttachCallback(Document $doc)
	{
		// Add core and base uri paths so javascript scripts can use them.
		$doc->addScriptOptions(
			'system.paths',
			[
				'root' => Uri::root(true),
				'rootFull' => Uri::root(),
				'base' => Uri::base(true),
				'baseFull' => Uri::base(),
			]
		);

		HTMLHelper::_('form.csrf');
	}
}
