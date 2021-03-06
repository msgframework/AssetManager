<?php

namespace Msgframework\Lib\AssetManager;

use Msgframework\Lib\AssetManager\Event\WebAssetEvent;
use Msgframework\Lib\AssetManager\Exception\InvalidActionException;
use Msgframework\Lib\AssetManager\Exception\UnknownAssetException;
use Msgframework\Lib\AssetManager\Exception\UnsatisfiedDependencyException;

/**
 * Web Asset Manager class
 */
class WebAssetManager implements WebAssetManagerInterface
{
	/**
	 * Mark inactive asset
	 *
	 * @var    integer
	 */
	const ASSET_STATE_INACTIVE = 0;

	/**
	 * Mark active asset. Just enabled, but WITHOUT dependency resolved
	 *
	 * @var    integer
	 */
	const ASSET_STATE_ACTIVE = 1;

	/**
	 * Mark active asset that is enabled as dependency to another asset
	 *
	 * @var    integer
	 */
	const ASSET_STATE_DEPENDENCY = 2;

	/**
	 * The WebAsset Registry instance
	 *
	 * @var    WebAssetRegistry
	 */
	protected WebAssetRegistry $registry;

	/**
	 * A list of active assets (including their dependencies).
	 * Array of Name => State
	 *
	 * @var    array
	 */
	protected array $activeAssets = [];

	/**
	 * Internal marker to check the manager state,
	 * to prevent use of the manager after an assets are rendered
	 *
	 * @var    boolean
	 */
	protected bool $locked = false;

	/**
	 * Internal marker to keep track when need to recheck dependencies
	 *
	 * @var    boolean
	 */
	protected bool $dependenciesIsActual = false;

	/**
	 * Class constructor
	 *
	 * @param   WebAssetRegistry  $registry  The WebAsset Registry instance
	 */
	public function __construct(WebAssetRegistry $registry)
	{
		$this->registry = $registry;

		// Listen to changes in the registry
		$this->registry->getDispatcher()->addListener(
			'onWebAssetRegistryChangedAssetOverride',
			function (WebAssetEvent $event)
			{
				// If the changed asset are used
				if (!empty($this->activeAssets[$event->getAssetType()][$event->getAsset()->getName()]))
				{
					$this->dependenciesIsActual = false;
				}
			}
		);

		$this->registry->getDispatcher()->addListener(
			'onWebAssetRegistryChangedAssetRemove',
			function (WebAssetEvent $event)
			{
				// If the changed asset are used
				if (!empty($this->activeAssets[$event->getAssetType()][$event->getAsset()->getName()]))
				{
					$this->dependenciesIsActual = false;
				}
			}
		);
	}

	/**
	 * Get associated registry instance
	 *
	 * @return   WebAssetRegistry
	 */
	public function getRegistry(): WebAssetRegistry
	{
		return $this->registry;
	}

    /**
     * Clears all collected items.
     *
     * @return self
     *
     */
    public function reset(): WebAssetManagerInterface
    {
        if ($this->locked)
        {
            throw new InvalidActionException('WebAssetManager is locked');
        }

        $this->activeAssets = [];
        $this->dependenciesIsActual = false;

        return $this;
    }

	/**
	 * Adds support for magic method calls
	 *
	 * @param   string  $method     A method name
	 * @param   array   $arguments  Arguments for a method
	 *
	 * @return mixed
	 *
	 * @throws  \BadMethodCallException
	 */
	public function __call(string $method, array $arguments)
	{
		$method = strtolower($method);

		if (0 === strpos($method, 'use'))
		{
			$type = substr($method, 3);

			if (empty($arguments[0]))
			{
				throw new \BadMethodCallException('An asset name is required');
			}

			return $this->useAsset($type, $arguments[0]);
		}

		if (0 === strpos($method, 'addinline'))
		{
			$type = substr($method, 9);

			if (empty($arguments[0]))
			{
				throw new \BadMethodCallException('Content is required');
			}

			return $this->addInline($type, ...$arguments);
		}

		if (0 === strpos($method, 'disable'))
		{
			$type = substr($method, 7);

			if (empty($arguments[0]))
			{
				throw new \BadMethodCallException('An asset name is required');
			}

			return $this->disableAsset($type, $arguments[0]);
		}

		if (0 === strpos($method, 'register'))
		{
			// Check for registerAndUse<Type>
			$andUse = substr($method, 8, 6) === 'anduse';

			// Extract the type
			$type = $andUse ? substr($method, 14) : substr($method, 8);

			if (empty($arguments[0]))
			{
				throw new \BadMethodCallException('An asset instance or an asset name is required');
			}

			if ($andUse)
			{
				$name = $arguments[0] instanceof WebAssetItemInterface ? $arguments[0]->getName() : $arguments[0];

				return $this->registerAsset($type, ...$arguments)->useAsset($type, $name);
			}
			else
			{
				return $this->registerAsset($type, ...$arguments);
			}
		}

		throw new \BadMethodCallException(sprintf('Undefined method %s in class %s', $method, get_class($this)));
	}

	/**
	 * Enable an asset item to be attached to a Document
	 *
	 * @param   string  $type  The asset type, script or style
	 * @param   string  $name  The asset name
	 *
	 * @return WebAssetManagerInterface
	 *
	 * @throws  UnknownAssetException  When Asset cannot be found
	 * @throws  InvalidActionException When the Manager already attached to a Document
	 */
	public function useAsset(string $type, string $name): WebAssetManagerInterface
	{
		if ($this->locked)
		{
			throw new InvalidActionException('WebAssetManager is locked, you came late');
		}

		// Check whether asset exists
		$asset = $this->registry->get($type, $name);

		if (empty($this->activeAssets[$type]))
		{
			$this->activeAssets[$type] = [];
		}

		// For "preset" need to check the dependencies first
		if ($type === 'preset')
		{
			$this->usePresetItems($name);
		}

		// Asset already enabled
		if (!empty($this->activeAssets[$type][$name]))
		{
			// Set state to active, in case it was ASSET_STATE_DEPENDENCY
			$this->activeAssets[$type][$name] = static::ASSET_STATE_ACTIVE;

			return $this;
		}

		$this->activeAssets[$type][$name] = static::ASSET_STATE_ACTIVE;

		// To re-check dependencies
		if ($asset->getDependencies())
		{
			$this->dependenciesIsActual = false;
		}

		return $this;
	}

	/**
	 * Deactivate an asset item, so it will not be attached to a Document
	 *
	 * @param   string  $type  The asset type, script or style
	 * @param   string  $name  The asset name
	 *
	 * @return  self
	 *
	 * @throws  UnknownAssetException  When Asset cannot be found
	 * @throws  InvalidActionException When the Manager already attached to a Document
	 */
	public function disableAsset(string $type, string $name): WebAssetManagerInterface
	{
		if ($this->locked)
		{
			throw new InvalidActionException('WebAssetManager is locked, you came late');
		}

		// Check whether asset exists
		$this->registry->get($type, $name);

		unset($this->activeAssets[$type][$name]);

		// To re-check dependencies
		$this->dependenciesIsActual = false;

		// For Preset case
		if ($type === 'preset')
		{
			$this->disablePresetItems($name);
		}

		return $this;
	}

	/**
	 * Enable list of assets provided by Preset item.
	 *
	 * "Preset" a special kind of asset that hold a list of assets that has to be enabled,
	 * same as direct call of useAsset() to each of item in list.
	 * Can hold mixed types of assets (script, style, another preset, etc), the type provided after # symbol, after
	 * the asset name, example: foo#style, bar#script.
	 *
	 * The method call useAsset() internally for each of its dependency, this is important for keeping FIFO order
	 * of enabled items.
	 * The Preset not a strict asset, and each of its dependency can be safely disabled by use of disableAsset() later.
	 *
	 * @param string $name  The asset name
	 *
	 * @return WebAssetManagerInterface
	 *
	 * @throws  UnsatisfiedDependencyException  When Asset dependency cannot be found
	 */
	protected function usePresetItems(string $name): WebAssetManagerInterface
	{
		// Get the asset object
		$asset = $this->registry->get('preset', $name);

		// Call useAsset() to each of its dependency
		foreach ($asset->getDependencies() as $dependency)
		{
			$depType = '';
			$depName = $dependency;
			$pos     = strrpos($dependency, '#');

			// Check for cross-dependency "dependency-name#type" case
			if ($pos)
			{
				$depType = substr($dependency, $pos + 1);
				$depName = substr($dependency, 0, $pos);
			}

            $depType = $depType ?: 'preset';

			// Make sure dependency exists
			if (!$this->registry->exists($depType, $depName))
			{
				throw new UnsatisfiedDependencyException(
					sprintf('Unsatisfied dependency "%s" for an asset "%s" of type "%s"', $dependency, $name, 'preset')
				);
			}

			$this->useAsset($depType, $depName);
		}

		return $this;
	}

	/**
	 * Deactivate list of assets provided by Preset item.
	 *
	 * @param string $name  The asset name
	 *
	 * @return  self
	 *
	 * @throws  UnsatisfiedDependencyException  When Asset dependency cannot be found
	 */
	protected function disablePresetItems(string $name): WebAssetManagerInterface
	{
		// Get the asset object
		$asset = $this->registry->get('preset', $name);

		// Call disableAsset() to each of its dependency
		foreach ($asset->getDependencies() as $dependency)
		{
			$depType = '';
			$depName = $dependency;
			$pos     = strrpos($dependency, '#');

			// Check for cross-dependency "dependency-name#type" case
			if ($pos)
			{
				$depType = substr($dependency, $pos + 1);
				$depName = substr($dependency, 0, $pos);
			}

			$depType = $depType ? $depType : 'preset';

			// Make sure dependency exists
			if (!$this->registry->exists($depType, $depName))
			{
				throw new UnsatisfiedDependencyException(
					sprintf('Unsatisfied dependency "%s" for an asset "%s" of type "%s"', $dependency, $name, 'preset')
				);
			}

			$this->disableAsset($depType, $depName);
		}

		return $this;
	}

	/**
	 * Get a state for the Asset
	 *
	 * @param   string  $type  The asset type, script or style
	 * @param   string  $name  The asset name
	 *
	 * @return  integer
	 *
	 * @throws  UnknownAssetException  When Asset cannot be found
	 */
	public function getAssetState(string $type, string $name): int
	{
		// Check whether asset exists first
		$this->registry->get($type, $name);

		// Make sure that all dependencies are active
		if (!$this->dependenciesIsActual)
		{
			$this->enableDependencies();
		}

		if (!empty($this->activeAssets[$type][$name]))
		{
			return $this->activeAssets[$type][$name];
		}

		return static::ASSET_STATE_INACTIVE;
	}

	/**
	 * Check whether the asset are enabled
	 *
	 * @param   string  $type  The asset type, script or style
	 * @param   string  $name  The asset name
	 *
	 * @return  boolean
	 *
	 * @throws  UnknownAssetException  When Asset cannot be found
	 */
	public function isAssetActive(string $type, string $name): bool
	{
		return $this->getAssetState($type, $name) !== static::ASSET_STATE_INACTIVE;
	}

	/**
	 * Helper method to check whether the asset exists in the registry.
	 *
	 * @param   string  $type  Asset type, script or style
	 * @param   string  $name  Asset name
	 *
	 * @return  boolean
	 */
	public function assetExists(string $type, string $name): bool
	{
		return $this->registry->exists($type, $name);
	}

	/**
	 * Register a new asset.
	 * Allow to register WebAssetItem instance in the registry, by call registerAsset($type, $assetInstance)
	 * Or create an asset on fly (from name and Uri) and register in the registry, by call registerAsset($type, $assetName, $uri, $options ....)
	 *
	 * @param   string               $type          The asset type, script or style
	 * @param   WebAssetItem|string  $asset         The asset name or instance to register
	 * @param   string               $uri           The URI for the asset
	 * @param   array                $options       Additional options for the asset
	 * @param   array                $attributes    Attributes for the asset
	 * @param   array                $dependencies  Asset dependencies
	 *
	 * @return  WebAssetManagerInterface
	 *
	 * @throws  \InvalidArgumentException
	 */
	public function registerAsset(string $type, $asset, string $uri = '', array $options = [], array $attributes = [], array $dependencies = []): WebAssetManagerInterface
	{
		if ($asset instanceof WebAssetItemInterface)
		{
			$this->registry->add($type, $asset);
		}
		elseif (is_string($asset))
		{
			$options['type'] = $type;
			$assetInstance = $this->registry->createAsset($asset, $uri, $options, $attributes, $dependencies);
			$this->registry->add($type, $assetInstance);
		}
		else
		{
			throw new \InvalidArgumentException(
				sprintf(
					'%s(): Argument #2 ($asset) must be a string or an instance of %s, %s given.',
					__METHOD__,
					WebAssetItemInterface::class,
					\is_object($asset) ? \get_class($asset) : \gettype($asset)
				)
			);
		}

		return $this;
	}

	/**
	 * Helper method to get the asset from the registry.
	 *
	 * @param   string  $type  Asset type, script or style
	 * @param   string  $name  Asset name
	 *
	 * @return  WebAssetItemInterface
	 *
	 * @throws  UnknownAssetException  When Asset cannot be found
	 */
	public function getAsset(string $type, string $name): WebAssetItemInterface
	{
		return $this->registry->get($type, $name);
	}

	/**
	 * Get all active assets, optionally sort them to follow the dependency Graph
	 *
	 * @param   string  $type  The asset type, script or style
	 * @param   bool    $sort  Whether need to sort the assets to follow the dependency Graph
	 *
	 * @return  WebAssetItem[]
	 *
	 * @throws  UnknownAssetException  When Asset cannot be found
	 * @throws  UnsatisfiedDependencyException When Dependency cannot be found
	 */
	public function getAssets(string $type, bool $sort = false): array
	{
		// Make sure that all dependencies are active
		if (!$this->dependenciesIsActual)
		{
			$this->enableDependencies();
		}

		if (empty($this->activeAssets[$type]))
		{
			return [];
		}

		// Apply Tree sorting for regular asset items, but return FIFO order for "preset"
		if ($sort && $type !== 'preset')
		{
			$assets = $this->calculateOrderOfActiveAssets($type);
		}
		else
		{
			$assets = [];

			foreach (array_keys($this->activeAssets[$type]) as $name)
			{
				$assets[$name] = $this->registry->get($type, $name);
			}
		}

		return $assets;
	}

	/**
	 * Helper method to calculate inline to non inline relation (before/after positions).
	 * Return associated array, which contain dependency (handle) name as key, and list of inline items for each position.
	 * Example: ['handle.name' => ['before' => ['inline1', 'inline2'], 'after' => ['inline3', 'inline4']]]
	 *
	 * Note: If inline asset have a multiple dependencies, then will be used last one from the list for positioning
	 *
	 * @param   WebAssetItem[]  $assets  The assets list
	 *
	 * @return  array
	 */
	public function getInlineRelation(array $assets): array
	{
		$inlineRelation = [];

		// Find an inline assets and their relations to non inline
		foreach ($assets as $k => $asset)
		{
			if (!$asset->getOption('inline'))
			{
				continue;
			}

			// Add to list of inline assets
			$inlineAssets[$asset->getName()] = $asset;

			// Check whether position are requested with dependencies
			$position = $asset->getOption('position');
			$position = $position === 'before' || $position === 'after' ? $position : null;
			$deps     = $asset->getDependencies();

			if ($position && $deps)
			{
				// If inline asset have a multiple dependencies, then use last one from the list for positioning
				$handle = end($deps);
				$inlineRelation[$handle][$position][$asset->getName()] = $asset;
			}
		}

		return $inlineRelation;
	}

	/**
	 * Helper method to filter an inline assets
	 *
	 * @param   WebAssetItem[]  $assets  Reference to a full list of active assets
	 *
	 * @return  WebAssetItem[]  Array of inline assets
	 */
	public function filterOutInlineAssets(array &$assets): array
	{
		$inlineAssets = [];

		foreach ($assets as $k => $asset)
		{
			if (!$asset->getOption('inline'))
			{
				continue;
			}

			// Remove inline assets from assets list, and add to list of inline
			unset($assets[$k]);

			$inlineAssets[$asset->getName()] = $asset;
		}

		return $inlineAssets;
	}

	/**
	 * Add a new inline content asset.
	 * Allow registering WebAssetItem instance in the registry, by call addInline($type, $assetInstance)
	 * Or create an asset on fly (from name and Uri) and register in the registry, by call addInline($type, $content, $options ....)
	 *
	 * @param   string               $type          The asset type, script or style
	 * @param   WebAssetItem|string  $content       The content to of inline asset
	 * @param   array                $options       Additional options for the asset
	 * @param   array                $attributes    Attributes for the asset
	 * @param   array                $dependencies  Asset dependencies
	 *
	 * @return  self
	 *
	 * @throws \InvalidArgumentException
	 */
	public function addInline(string $type, $content, array $options = [], array $attributes = [], array $dependencies = []): self
	{
		if ($content instanceof WebAssetItemInterface)
		{
			$assetInstance = $content;
		}
		elseif (is_string($content))
		{
			$name          = $options['name'] ?? ('inline.' . md5($content));
			$assetInstance = $this->registry->createAsset($name, '', $options, $attributes, $dependencies);
			$assetInstance->setOption('content', $content);
		}
		else
		{
			throw new \InvalidArgumentException(
				sprintf(
					'%s(): Argument #2 ($content) must be a string or an instance of %s, %s given.',
					__METHOD__,
					WebAssetItemInterface::class,
					\is_object($content) ? \get_class($content) : \gettype($content)
				)
			);
		}

		// Get the name
		$asset = $assetInstance->getName();

		// Set required options
		$assetInstance->setOption('type', $type);
		$assetInstance->setOption('inline', true);

		// Add to registry
		$this->registry->add($type, $assetInstance);

		// And make active
		$this->useAsset($type, $asset);

		return $this;
	}

	/**
	 * Lock the manager to prevent further modifications
	 *
	 * @return WebAssetManagerInterface
	 */
	public function lock(): WebAssetManagerInterface
	{
		$this->locked = true;

		return $this;
	}

	/**
	 * Get the manager state. A collection of registry files and active asset names (per type).
	 *
	 * @return array
	 */
	public function getManagerState(): array
	{
		return [
			'registryFiles' => $this->getRegistry()->getRegistryFiles(),
			'activeAssets'  => $this->activeAssets,
		];
	}

    /**
     * Update Dependencies state for all active Assets or only for given
     *
     * @param string|null $type The asset type, script or style
     * @param WebAssetItem|null $asset The asset instance to which need to enable dependencies
     *
     * @return  WebAssetManagerInterface
     */
	protected function enableDependencies(?string $type = null, ?WebAssetItem $asset = null): WebAssetManagerInterface
	{
		if ($type === 'preset')
		{
			// Preset items already was enabled by usePresetItems()
			return $this;
		}

		if ($asset)
		{
			// Get all dependencies of given asset recursively
			$allDependencies = $this->getDependenciesForAsset($type, $asset, true);

			foreach ($allDependencies as $depType => $depItems)
			{
				foreach ($depItems as $depItem)
				{
					// Set dependency state only when it is inactive, to keep a manually activated Asset in their original state
					if (empty($this->activeAssets[$depType][$depItem->getName()]))
					{
						// Add the dependency at the top of the list of active assets
						$this->activeAssets[$depType] = [$depItem->getName() => static::ASSET_STATE_DEPENDENCY] + $this->activeAssets[$depType];
					}
				}
			}
		}
		else
		{
			// Re-Check for dependencies for all active assets
			// Firstly, filter out only active assets
			foreach ($this->activeAssets as $type => $activeAsset)
			{
				$this->activeAssets[$type] = array_filter(
					$activeAsset,
					function ($state) {
						return $state === WebAssetManager::ASSET_STATE_ACTIVE;
					}
				);
			}

			// Secondary, check for dependencies of each active asset
			// This need to be separated from previous step because we may have "cross type" dependency
			foreach ($this->activeAssets as $type => $activeAsset)
			{
				foreach (array_keys($activeAsset) as $name)
				{
					$asset = $this->registry->get($type, $name);
					$this->enableDependencies($type, $asset);
				}
			}

			$this->dependenciesIsActual = true;
		}

		return $this;
	}

	/**
	 * Calculate weight of active Assets, by its Dependencies
	 *
	 * @param   string  $type  The asset type, script or style
	 *
	 * @return  WebAssetItem[]
	 */
	protected function calculateOrderOfActiveAssets($type): array
	{
		// See https://en.wikipedia.org/wiki/Topological_sorting#Kahn.27s_algorithm
		$graphOrder    = [];
		$activeAssets  = $this->getAssets($type, false);

		// Get Graph of Outgoing and Incoming connections
		$connectionsGraph = $this->getConnectionsGraph($activeAssets);
		$graphOutgoing    = $connectionsGraph['outgoing'];
		$graphIncoming    = $connectionsGraph['incoming'];

		// Make a copy to be used during weight processing
		$graphIncomingCopy = $graphIncoming;

		// Find items without incoming connections
		$emptyIncoming = array_keys(
			array_filter(
				$graphIncoming,
				function ($el) {
					return !$el;
				}
			)
		);

		// Reverse, to start from a last enabled and move up to a first enabled, this helps to maintain an original sorting
		$emptyIncoming = array_reverse($emptyIncoming);

		// Loop through, and sort the graph
		while ($emptyIncoming)
		{
			// Add the node without incoming connection to the result
			$item = array_shift($emptyIncoming);
			$graphOrder[] = $item;

			// Check of each neighbor of the node
			foreach (array_reverse($graphOutgoing[$item]) as $neighbor)
			{
				// Remove incoming connection of already visited node
				unset($graphIncoming[$neighbor][$item]);

				// If there no more incoming connections add the node to queue
				if (empty($graphIncoming[$neighbor]))
				{
					$emptyIncoming[] = $neighbor;
				}
			}
		}

		// Sync Graph order with FIFO order
		$fifoWeights      = [];
		$graphWeights     = [];
		$requestedWeights = [];

		foreach (array_keys($this->activeAssets[$type]) as $index => $name)
		{
			$fifoWeights[$name] = $index * 10 + 10;
		}

		foreach (array_reverse($graphOrder) as $index => $name)
		{
			$graphWeights[$name]     = $index * 10 + 10;
			$requestedWeights[$name] = $activeAssets[$name]->getOption('weight') ?: $fifoWeights[$name];
		}

		// Try to set a requested weight, or make it close as possible to requested, but keep the Graph order
		while ($requestedWeights)
		{
			$item   = key($requestedWeights);
			$weight = array_shift($requestedWeights);

			// Skip empty items
			if ($weight === null)
			{
				continue;
			}

			// Check the predecessors (Outgoing vertexes), the weight cannot be lighter than the predecessor have
			$topBorder = $weight - 1;

			if (!empty($graphOutgoing[$item]))
			{
				$prevWeights = [];

				foreach ($graphOutgoing[$item] as $pItem)
				{
					$prevWeights[] = $graphWeights[$pItem];
				}

				$topBorder = max($prevWeights);
			}

			// Calculate a new weight
			$newWeight = $weight > $topBorder ? $weight : $topBorder + 1;

			// If a new weight heavier than existing, then we need to update all incoming connections (children)
			if ($newWeight > $graphWeights[$item] && !empty($graphIncomingCopy[$item]))
			{
				// Sort Graph of incoming by actual position
				foreach ($graphIncomingCopy[$item] as $incomingItem)
				{
					// Set a weight heavier than current, then this node to be processed in next iteration
					if (empty($requestedWeights[$incomingItem]))
					{
						$requestedWeights[$incomingItem] = $graphWeights[$incomingItem] + $newWeight;
					}
				}
			}

			// Set a new weight
			$graphWeights[$item] = $newWeight;
		}

		asort($graphWeights);

		// Get Assets in calculated order
		$resultAssets  = [];

		foreach (array_keys($graphWeights) as $name)
		{
			$resultAssets[$name] = $activeAssets[$name];
		}

		return $resultAssets;
	}

	/**
	 * Build Graph of Outgoing and Incoming connections for given assets.
	 *
	 * @param   WebAssetItem[]  $assets  Asset instances
	 *
	 * @return  array
	 */
	protected function getConnectionsGraph(array $assets): array
	{
		$graphOutgoing = [];
		$graphIncoming = [];

		foreach ($assets as $asset)
		{
			$name = $asset->getName();

			// Initialise an array for outgoing nodes of the asset
			$graphOutgoing[$name] = [];

			// Initialise an array for incoming nodes of the asset
			if (!\array_key_exists($name, $graphIncoming))
			{
				$graphIncoming[$name] = [];
			}

			// Collect an outgoing/incoming nodes
			foreach ($asset->getDependencies() as $depName)
			{
				$graphOutgoing[$name][$depName] = $depName;
				$graphIncoming[$depName][$name] = $name;
			}
		}

		return [
			'outgoing' => $graphOutgoing,
			'incoming' => $graphIncoming,
		];
	}

    /**
     * Return dependencies for Asset as array of WebAssetItem objects
     *
     * @param string $type The asset type, script or style
     * @param WebAssetItem $asset Asset instance
     * @param boolean $recursively Whether to search for dependency recursively
     * @param string|null $recursionType The type of initial item to prevent loop
     * @param WebAssetItem|null $recursionRoot Initial item to prevent loop
     *
     * @return  array
     *
     */
	protected function getDependenciesForAsset(
        string       $type,
        WebAssetItem $asset,
        bool         $recursively = false,
        string       $recursionType = null,
        WebAssetItem $recursionRoot = null
	): array
	{
		$assets        = [];
		$recursionRoot = $recursionRoot ?? $asset;
		$recursionType = $recursionType ?? $type;

		foreach ($asset->getDependencies() as $depName)
		{
			$depType = $type;

			// Skip already loaded in recursion
			if ($recursionRoot->getName() === $depName && $recursionType === $depType)
			{
				continue;
			}

			if (!$this->registry->exists($depType, $depName))
			{
				throw new UnsatisfiedDependencyException(
					sprintf('Unsatisfied dependency "%s" for an asset "%s" of type "%s"', $depName, $asset->getName(), $depType)
				);
			}

			$dep = $this->registry->get($depType, $depName);

			$assets[$depType][$depName] = $dep;

			if (!$recursively)
			{
				continue;
			}

			$parentDeps = $this->getDependenciesForAsset($depType, $dep, true, $recursionType, $recursionRoot);
			$assets     = array_replace_recursive($assets, $parentDeps);
		}

		return $assets;
	}
}
