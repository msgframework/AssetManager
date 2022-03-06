<?php

namespace Msgframework\Lib\AssetManager;


use Msgframework\Lib\AssetManager\Exception\InvalidActionException;
use Msgframework\Lib\AssetManager\Exception\UnknownAssetException;
use Msgframework\Lib\AssetManager\Exception\UnsatisfiedDependencyException;

/**
 * Web Asset Manager Interface
 */
interface WebAssetManagerInterface
{
	/**
	 * Enable an asset item to be attached to a Document
	 *
	 * @param   string  $type  Asset type, script or style etc
	 * @param   string  $name  The asset name
	 *
	 * @return self
	 *
	 * @throws  UnknownAssetException  When Asset cannot be found
	 * @throws  InvalidActionException When the Manager already attached to a Document
	 */
	public function useAsset(string $type, string $name): self;

	/**
	 * Deactivate an asset item, so it will not be attached to a Document
	 *
	 * @param   string  $type  Asset type, script or style etc
	 * @param   string  $name  The asset name
	 *
	 * @return self
	 *
	 * @throws  UnknownAssetException  When Asset cannot be found
	 * @throws  InvalidActionException When the Manager already attached to a Document
	 *
	 * @since  1.1.0
	 */
	public function disableAsset(string $type, string $name): self;

	/**
	 * Check whether the asset are enabled
	 *
	 * @param   string  $type  Asset type, script or style etc
	 * @param   string  $name  The asset name
	 *
	 * @return  boolean
	 *
	 * @throws  UnknownAssetException  When Asset cannot be found
	 *
	 * @since  1.1.0
	 */
	public function isAssetActive(string $type, string $name): bool;

	/**
	 * Get all assets that was enabled for given type
	 *
	 * @param   string  $type  Asset type, script or style etc
	 * @param   bool    $sort  Whether need to sort the assets to follow the dependency Graph
	 *
	 * @return  WebAssetItemInterface[]
	 *
	 * @throws  UnknownAssetException  When Asset cannot be found
	 * @throws  UnsatisfiedDependencyException When Dependency cannot be found
	 *
	 * @since  1.1.0
	 */
	public function getAssets(string $type, bool $sort = false): array;

}

