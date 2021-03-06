<?php

namespace Msgframework\Lib\AssetManager\AssetItem;


use Joomla\CMS\Factory;
use Msgframework\Lib\AssetManager\WebAssetItem;

/**
 * Web Asset Item class for load asset file for active language.
 * Used in core templates.
 */
class LangActiveAssetItem extends WebAssetItem
{
    /**
     * Class constructor
     *
     * @param string $name The asset name
     * @param string|null $uri The URI for the asset
     * @param array $options Additional options for the asset
     * @param array $attributes Attributes for the asset
     * @param array $dependencies Asset dependencies
     */
	public function __construct(
		string $name,
		string $uri = null,
		array $options = [],
		array $attributes = [],
		array $dependencies = []
	)
	{
		parent::__construct($name, $uri, $options, $attributes, $dependencies);

		// Prepare Uri depend from the active language
		$langTag = Factory::getApplication()->getLanguage()->getTag();
		$client  = $this->getOption('client');

		// Create Uri <client>/language/<langTag>/<langTag>.css
		if ($client)
		{
			$this->uri = $client . '/language/' . $langTag . '/' . $langTag . '.css';
		}
		else
		{
			$this->uri = 'language/' . $langTag . '/' . $langTag . '.css';
		}
	}
}
