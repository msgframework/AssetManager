<?php

namespace Msgframework\Lib\AssetManager\AssetItem;

use RocketCMS\Lib\Document\Document;
use Joomla\CMS\Language\Text;
use Msgframework\Lib\AssetManager\WebAssetAttachBehaviorInterface;
use Msgframework\Lib\AssetManager\WebAssetItem;

/**
 * Web Asset Item class for form.validate asset
 */
class FormValidateAssetItem extends WebAssetItem implements WebAssetAttachBehaviorInterface
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
		// Add validate.js language strings
		Text::script('JLIB_FORM_CONTAINS_INVALID_FIELDS');
		Text::script('JLIB_FORM_FIELD_REQUIRED_VALUE');
		Text::script('JLIB_FORM_FIELD_REQUIRED_CHECK');
		Text::script('JLIB_FORM_FIELD_INVALID_VALUE');
	}
}
