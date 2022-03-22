<?php

namespace Msgframework\Lib\AssetManager;

use League\Uri\Uri;
use League\Uri\UriInfo;

/**
 * Web Asset Item class
 *
 * Asset Item are "read only" object, all properties must be set through class constructor.
 * Only properties allowed to be edited is an attributes and an options.
 * Changing an uri or a dependencies are not allowed, prefer to create a new asset instance.
 */
class WebAssetItem implements WebAssetItemInterface
{
    /**
     * Asset name
     *
     * @var    string $name
     */
    protected string $name = '';

    /**
     * The URI for the asset
     *
     * @var    string|null
     */
    protected ?string $uri = '';

    /**
     * Additional options for the asset
     *
     * @var    array
     */
    protected array $options = array();

    /**
     * Attributes for the asset, to be rendered in the asset's HTML tag
     *
     * @var    array
     */
    protected array $attributes = array();

    /**
     * Asset dependencies
     *
     * @var    string[]
     */
    protected array $dependencies = array();

    /**
     * Asset version
     *
     * @var    string
     */
    protected string $version = 'auto';

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
        string  $name,
        ?string $uri = null,
        array   $options = array(),
        array   $attributes = array(),
        array   $dependencies = array()
    )
    {
        $this->name = $name;
        $this->uri = $uri;

        if (array_key_exists('attributes', $options)) {
            $this->attributes = (array)$options['attributes'];
            unset($options['attributes']);
        } else {
            $this->attributes = $attributes;
        }

        if (array_key_exists('dependencies', $options)) {
            $this->dependencies = (array)$options['dependencies'];
            unset($options['dependencies']);
        } else {
            $this->dependencies = $dependencies;
        }

        $this->options = $options;
    }

    /**
     * Return Asset name
     *
     * @return  string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set Asset version
     *
     * @param mixed $version
     *
     * @return self
     */
    public function setVersion($version): self
    {
        $this->version = (string)$version;
        return $this;
    }

    /**
     * Return Asset version
     *
     * @return  string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Return dependencies list
     *
     * @return  array
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * Get the file path
     *
     * @param boolean $resolvePath Whether you need to search for a real paths
     *
     * @return  string  The resolved path if resolved, else an empty string.
     */
    public function getUri(bool $resolvePath = true): string
    {
        $path = $this->uri;

        if ($resolvePath && $path) {
            switch (pathinfo($path, PATHINFO_EXTENSION)) {
                case 'js':
                    $path = $this->resolvePath($path, 'script');
                    break;
                case 'css':
                    $path = $this->resolvePath($path, 'stylesheet');
                    break;
                default:
                    break;
            }
        }

        return $path ?? '';
    }

    /**
     * Get the option
     *
     * @param string $key An option key
     * @param string $default A default value
     *
     * @return mixed
     */
    public function getOption(string $key, $default = null)
    {
        if (array_key_exists($key, $this->options)) {
            return $this->options[$key];
        }

        return $default;
    }

    /**
     * Set the option
     *
     * @param string $key An option key
     * @param string $value An option value
     *
     * @return self
     */
    public function setOption(string $key, $value = null): self
    {
        if ($key == 'version') {
            $this->setVersion($value);

            return $this;
        }

        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Get all options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get the attribute
     *
     * @param string $key An attributes key
     * @param string $default A default value
     *
     * @return mixed
     */
    public function getAttribute(string $key, $default = null)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        return $default;
    }

    /**
     * Set the attribute
     *
     * @param string $key An attribute key
     * @param string $value An attribute value
     *
     * @return self
     */
    public function setAttribute(string $key, $value = null): WebAssetItemInterface
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Get all attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Resolve path
     *
     * @param string $path The path to resolve
     * @param string $type The resolver method
     *
     * @return string
     */
    protected function resolvePath(string $path, string $type): string
    {
        if ($type !== 'script' && $type !== 'stylesheet') {
            throw new \UnexpectedValueException(sprintf('Unexpected type is %s, expected "script" or "stylesheet"', $type));
        }

        $file = $path;

        $uri = Uri::createFromString($path);

        if(!UriInfo::isNetworkPath($uri)) {
            //TODO Реализовать вывод правильного пути до локального файла
        }

        return $file ?? '';
    }
}
