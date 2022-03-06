<?php

namespace Msgframework\Lib\AssetManager;

/**
 * Web Asset Item interface
 *
 * Asset Item are "read only" object, all properties must be set through class constructor.
 * Only properties allowed to be edited is an attributes and an options.
 * Changing an uri or a dependencies are not allowed, prefer to create a new asset instance.
 */
interface WebAssetItemInterface
{
	/**
	 * Return Asset name
	 *
	 * @return  string
	 */
	public function getName(): string;

	/**
	 * Return Asset version
	 *
	 * @return  string
	 */
	public function getVersion(): string;

	/**
	 * Return dependencies list
	 *
	 * @return  array
	 */
	public function getDependencies(): array;

	/**
	 * Get the URI of the asset
	 *
	 * @param   boolean  $resolvePath  Whether you need to search for a real paths
	 *
	 * @return string
	 */
	public function getUri(bool $resolvePath = true): string;

	/**
	 * Get the option
	 *
	 * @param   string  $key      An option key
	 * @param   string|null  $default  A default value
	 *
	 * @return mixed
	 */
	public function getOption(string $key, ?string $default = null);

	/**
	 * Set the option
	 *
	 * @param   string  $key    An option key
	 * @param   string|null  $value  An option value
	 *
	 * @return self
	 */
	public function setOption(string $key, ?string $value = null): self;

	/**
	 * Get all options of the asset
	 *
	 * @return array
	 */
	public function getOptions(): array;

	/**
	 * Get the attribute
	 *
	 * @param   string  $key      An attributes key
	 * @param   string|null  $default  A default value
	 *
	 * @return mixed
	 */
	public function getAttribute(string $key, ?string $default = null);

	/**
	 * Set the attribute
	 *
	 * @param   string  $key    An attribute key
	 * @param   string|null  $value  An attribute value
	 *
	 * @return self
	 */
	public function setAttribute(string $key, ?string $value = null): self;

	/**
	 * Get all attributes
	 *
	 * @return array
	 */
	public function getAttributes(): array;

}
