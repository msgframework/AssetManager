<?php

namespace Msgframework\Lib\AssetManager\AssetItem;


use Msgframework\Lib\Document\Document;
use Msgframework\Lib\Router\Route;
use Msgframework\Lib\AssetManager\WebAssetAttachBehaviorInterface;
use Msgframework\Lib\AssetManager\WebAssetItem;

/**
 * Web Asset Item class for Keepalive asset
 */
class KeepaliveAssetItem extends WebAssetItem implements WebAssetAttachBehaviorInterface
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
		$app            = \Cms::getApplication();
		$sessionHandler = $app->get('session_handler', 'database');

		// If the handler is not 'Database', we set a fixed, small refresh value (here: 5 min)
		$refreshTime = 300;

		if ($sessionHandler === 'database')
		{
			$lifeTime    = $app->getSession()->getExpire();
			$refreshTime = $lifeTime <= 60 ? 45 : $lifeTime - 60;

			// The longest refresh period is one hour to prevent integer overflow.
			if ($refreshTime > 3600 || $refreshTime <= 0)
			{
				$refreshTime = 3600;
			}
		}

		// If we are in the frontend or logged in as a user, we can use the ajax component to reduce the load
		$uri = 'index.php' . ($app->isClient('site') || !Factory::getUser()->guest ? '?option=com_ajax&format=json' : '');

		// Add keepalive script options.
		$doc->addScriptOptions('system.keepalive', array('interval' => $refreshTime * 1000, 'uri' => Route::_($uri)));
	}
}
