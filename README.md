# The Web assets manager library

[![Latest Stable Version](http://poser.pugx.org/msgframework/AssetManager/v)](https://packagist.org/packages/msgframework/AssetManager)
[![Total Downloads](http://poser.pugx.org/msgframework/AssetManager/downloads)](https://packagist.org/packages/msgframework/AssetManager)
[![Latest Unstable Version](http://poser.pugx.org/msgframework/AssetManager/v/unstable)](https://packagist.org/packages/msgframework/AssetManager)
[![License](http://poser.pugx.org/msgframework/AssetManager/license)](https://packagist.org/packages/msgframework/AssetManager)
[![PHP Version Require](http://poser.pugx.org/msgframework/AssetManager/require/php)](https://packagist.org/packages/msgframework/AssetManager)

## About



## Load AssetManagerRegistry

``` php
use RocketCMS\Lib\AssetManager\WebAssetRegistry as WebAssetRegistry;
$registry = new WebAssetRegistry;

// Add registry files
$registry
    ->addRegistryFile('media/vendor/media.asset.json')
    ->addRegistryFile('media/system/media.asset.json');
```

## Installation

You can install this package easily with [Composer](https://getcomposer.org/).

Just require the package with the following command:

    $ composer require msgframework/assetmanager
