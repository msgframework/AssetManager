<?php

namespace Msgframework\Lib\AssetManager;

use Msgframework\Lib\AssetManager\Event\WebAssetEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Msgframework\Lib\AssetManager\Exception\UnknownAssetException;

/**
 * Web Asset Registry class
 */
class WebAssetRegistry implements WebAssetRegistryInterface
{

    /**
     * The server root directory
     * @var string 
     */
    private string $root_dir;
    /**
     * Event Dispatcher
     *
     * @var    EventDispatcherInterface
     */
    private EventDispatcherInterface $dispatcher;

    /**
     * Files with Asset info. File path should be relative.
     *
     * @var    array
     */
    protected array $dataFilesNew = array();

    /**
     * List of parsed files
     *
     * @var array
     */
    protected array $dataFilesParsed = array();

    /**
     * Registry of available Assets
     *
     * @var array
     */
    protected array $assets = array();

    /**
     * Registry constructor
     * @param string $root
     */
    public function __construct(string $root)
    {
        $this->root_dir = $root;

        // Use a dedicated dispatcher
        $this->setDispatcher(new EventDispatcher);
    }

    /**
     * Get the event dispatcher.
     *
     * @return  EventDispatcherInterface
     *
     * @throws  \UnexpectedValueException May be thrown if the dispatcher has not been set.
     */
    public function getDispatcher(): EventDispatcherInterface
    {
        if (isset($this->dispatcher)) {
            return $this->dispatcher;
        }

        throw new \UnexpectedValueException('Dispatcher not set in ' . __CLASS__);
    }

    /**
     * Set the dispatcher to use.
     *
     * @param EventDispatcherInterface $dispatcher The dispatcher to use.
     *
     * @return  self
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher): self
    {
        $this->dispatcher = $dispatcher;

        return $this;
    }

    /**
     * Get an existing Asset from a registry, by asset name.
     *
     * @param string $type Asset type, script or style
     * @param string $name Asset name
     *
     * @return  WebAssetItem
     *
     * @throws  UnknownAssetException  When Asset cannot be found
     */
    public function get(string $type, string $name): WebAssetItemInterface
    {
        // Check if any new file was added
        $this->parseRegistryFiles();

        if (empty($this->assets[$type][$name])) {
            throw new UnknownAssetException(sprintf('There is no "%s" asset of a "%s" type in the registry.', $name, $type));
        }

        return $this->assets[$type][$name];
    }

    /**
     * Add Asset to registry of known assets
     *
     * @param string $type Asset type, script or style
     * @param WebAssetItemInterface $asset Asset instance
     *
     * @return  self
     */
    public function add(string $type, WebAssetItemInterface $asset): WebAssetRegistryInterface
    {
        $type = strtolower($type);

        if (!array_key_exists($type, $this->assets)) {
            $this->assets[$type] = array();
        }

        $eventType = 'add';
        $eventAsset = $asset;

        if (isset($this->assets[$type][$asset->getName()]) && !empty($this->assets[$type][$asset->getName()])) {
            $eventType = 'override';
            $eventAsset = $this->assets[$type][$asset->getName()];
        }

        $this->assets[$type][$asset->getName()] = $asset;

        $this->dispatchAssetChanged($type, $eventAsset, $eventType);

        return $this;
    }

    /**
     * Remove Asset from registry.
     *
     * @param string $type Asset type, script or style
     * @param string $name Asset name
     *
     * @return  self
     */
    public function remove(string $type, string $name): WebAssetRegistryInterface
    {
        // Check if any new file was added
        $this->parseRegistryFiles();

        if (!empty($this->assets[$type][$name])) {
            $asset = $this->assets[$type][$name];

            unset($this->assets[$type][$name]);

            $this->dispatchAssetChanged($type, $asset, 'remove');
        }

        return $this;
    }

    /**
     * Check whether the asset exists in the registry.
     *
     * @param string $type Asset type, script or style
     * @param string $name Asset name
     *
     * @return  boolean
     */
    public function exists(string $type, string $name): bool
    {
        // Check if any new file was added
        $this->parseRegistryFiles();

        return !empty($this->assets[$type][$name]);
    }

    /**
     * Prepare new Asset instance.
     *
     * @param string $name The asset name
     * @param string|null $uri The URI for the asset
     * @param array $options Additional options for the asset
     * @param array $attributes Attributes for the asset
     * @param array $dependencies Asset dependencies
     *
     * @return  WebAssetItem
     */
    public function createAsset(
        string $name,
        ?string $uri = null,
        array  $options = [],
        array  $attributes = [],
        array  $dependencies = []
    ): WebAssetItem
    {
        $nameSpace = \array_key_exists('namespace', $options) ? $options['namespace'] : __NAMESPACE__ . '\\AssetItem';
        $className = \array_key_exists('class', $options) ? $options['class'] : null;

        if ($className && class_exists($nameSpace . '\\' . $className)) {
            $className = $nameSpace . '\\' . $className;

            return new $className($name, $uri, $options, $attributes, $dependencies);
        }

        return new WebAssetItem($name, $uri, $options, $attributes, $dependencies);
    }

    /**
     * Register new file with Asset(s) info
     *
     * @param string $path Relative path
     *
     * @return  self
     */
    public function addRegistryFile(string $path): self
    {
        $path = str_replace('\\', DIRECTORY_SEPARATOR, $path);

        if (isset($this->dataFilesNew[$path]) || isset($this->dataFilesParsed[$path])) {
            return $this;
        }

        if (is_file($this->root_dir . DIRECTORY_SEPARATOR . $path)) {
            $this->dataFilesNew[$path] = $path;
        }

        return $this;
    }

    /**
     * Get a list of the registry files
     *
     * @return  array
     */
    public function getRegistryFiles(): array
    {
        return array_values($this->dataFilesParsed + $this->dataFilesNew);
    }

    /**
     * Parse registered files
     *
     * @return  void
     */
    protected function parseRegistryFiles()
    {
        if (!$this->dataFilesNew) {
            return;
        }

        foreach ($this->dataFilesNew as $path) {
            // Parse only if the file was not parsed already
            if (empty($this->dataFilesParsed[$path])) {
                $this->parseRegistryFile($path);

                // Mark the file as parsed
                $this->dataFilesParsed[$path] = $path;
            }

            // Remove the file from queue
            unset($this->dataFilesNew[$path]);
        }
    }

    /**
     * Parse registry file
     *
     * @param string $path Relative path to the data file
     *
     * @return  void
     *
     * @throws  \RuntimeException If file is empty or invalid
     */
    protected function parseRegistryFile(string $path): void
    {
        $data = file_get_contents($this->root_dir . DIRECTORY_SEPARATOR . $path);
        $data = $data ? json_decode($data, true) : null;

        if ($data === null) {
            throw new \RuntimeException(sprintf('Asset registry file "%s" contains invalid JSON', $path));
        }

        // Check if asset field exists and contains data. If it doesn't - we can just bail here.
        if (empty($data['assets'])) {
            return;
        }

        // Keep source info
        $assetSource = [
            'registryFile' => $path,
        ];

        $namespace = \array_key_exists('namespace', $data) ? $data['namespace'] : null;

        // Prepare WebAssetItem instances
        foreach ($data['assets'] as $i => $item) {
            if (empty($item['name'])) {
                throw new \RuntimeException(
                    sprintf('Failed parsing asset registry file "%s". Property "name" is required for asset index "%s"', $path, $i)
                );
            }

            if (empty($item['type'])) {
                throw new \RuntimeException(
                    sprintf('Failed parsing asset registry file "%s". Property "type" is required for asset "%s"', $path, $item['name'])
                );
            }

            $item['type'] = strtolower($item['type']);

            $name = $item['name'];
            $uri = isset($item['uri']) ? $assetSource['registryFileDir'] . $item['uri'] : '';
            $options = $item;
            $options['assetSource'] = $assetSource;

            unset($options['uri'], $options['name']);

            // Inheriting the Namespace
            if ($namespace && !\array_key_exists('namespace', $options)) {
                $options['namespace'] = $namespace;
            }

            $assetItem = $this->createAsset($name, $uri, $options);
            $this->add($item['type'], $assetItem);
        }
    }

    /**
     * Dispatch an event to notify listeners about asset changes: new, remove, override
     * Events:
     *  - onWebAssetRegistryChangedAssetAdd       When new asset added to the registry
     *  - onWebAssetRegistryChangedAssetOverride  When the asset overridden
     *  - onWebAssetRegistryChangedAssetRemove    When new asset was removed from the registry
     *
     * @param string $assetType Asset type, script or style
     * @param WebAssetItemInterface $asset Asset instance
     * @param string $eventType A type of change: new, remove, override
     *
     * @return  void
     */
    protected function dispatchAssetChanged(string $assetType, WebAssetItemInterface $asset, string $eventType)
    {
        // Trigger the event
        $event = new WebAssetEvent($this, $asset, $assetType, $eventType);

        $this->getDispatcher()->dispatch($event, 'onWebAssetRegistryChangedAsset' . ucfirst($eventType));
    }
}
