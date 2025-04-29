Nette Assets
============

[![Downloads this Month](https://img.shields.io/packagist/dm/nette/assets.svg)](https://packagist.org/packages/nette/assets)
[![Tests](https://github.com/nette/assets/workflows/Tests/badge.svg?branch=master)](https://github.com/nette/assets/actions)
[![Latest Stable Version](https://poser.pugx.org/nette/assets/v/stable)](https://github.com/nette/assets/releases)
[![License](https://img.shields.io/badge/license-New%20BSD-blue.svg)](https://github.com/nette/assets/blob/master/license.md)

 <!---->

Introduction
------------

Nette Assets is a powerful asset management library for PHP that helps you:

✅ organize and serve your static assets (images, CSS, JavaScript, audio, etc.)<br>
✅ handle asset versioning automatically<br>
✅ get image dimensions without hassle<br>
✅ verify asset existence<br>
✅ support multiple storage backends<br>
✅ integrate with Vite for modern frontend development

The library provides a clean and intuitive API to manage static assets in your web applications with a focus on developer experience and performance.

 <!---->

Installation and Requirements
-----------------------------

The recommended way to install is via Composer:

```shell
composer require nette/assets
```

Nette Assets requires PHP 8.1 or higher.

 <!---->

Core Concepts
-------------

The library revolves around a few key components:

- **Asset:** An interface representing a single static asset. All assets provide a public URL via `__toString()`. Different asset types (like `ImageAsset`, `ScriptAsset`, `AudioAsset`) provide type-specific properties.
- **Mapper:** An interface responsible for taking an asset reference (like `app.js` or `images/logo.png`) and resolving it into an `Asset` object. Different mappers can fetch assets from various sources (filesystem, CDN, cloud storage, manifest files). `FilesystemMapper` is the built-in implementation for serving files from a local directory. If the requested asset cannot be found, the mapper throws an `AssetNotFoundException`.
- **Registry:** A central service that holds all configured `Mapper` instances, each identified by a unique string ID (e.g., `'default'`, `'audio'`, `'images'`). It provides the main entry point (`getAsset()`) for retrieving assets using a **qualified reference**, which throws `AssetNotFoundException` if the requested asset cannot be found. For cases where handling non-existent assets without exceptions is preferred, it also provides `tryGetAsset()`, which returns `null` instead of throwing an exception.
- **Qualified Reference:** This identifies the specific asset you want to retrieve via the `Registry`. It supports three formats:
	- A simple string `reference` (e.g., `'app.js'`) which uses the `default` mapper.
	- A prefixed string `mapper:reference` (e.g., `'audio:podcast.mp3'`) which specifies the mapper explicitly.
	- An array `[mapper, reference]` (e.g., `['images', 'logo.png']`) which also specifies the mapper explicitly.

 <!---->

Asset Types
-----------

The library provides specialized asset interfaces and implementations for different content types:

- **ImageAsset** - Images with width, height, alternative text, and lazy loading support
- **ScriptAsset** - JavaScript files with dependencies, integrity hashes, and development mode support
- **StyleAsset** - CSS files with media queries and dependencies
- **AudioAsset** - Audio files with duration information
- **VideoAsset** - Video files with dimensions, duration, poster image, and autoplay settings
- **GenericAsset** - Generic files

 <!---->

Configuration
-------------

Configuration is typically done in your application's [NEON](https://ne-on.org) configuration file under the `assets` key.

You can define a base filesystem path and URL prefix under the main `assets:` key. These serve as the foundation from which relative paths defined in mappers are resolved.

```neon
assets:
	path: %wwwDir%/static
	url: /static
```

**However, explicit configuration is often optional.** If omitted, `path` typically defaults to your public web root (`%wwwDir%`), and `url` defaults to the application's base URL path (for example `https://domain/`).

The `mapping` section defines your named mappers. Each key in `mapping` is a mapper identifier (e.g., `default`, `audio`, `images`). Now, let's see how different mapper configurations under `mapping` behave relative to the base settings:

```neon
assets:
	# base settings
	path: %wwwDir%/static
	url: /static

	mapping:
		default: assets   # path becomes '%wwwDir%/static/assets', URL becomes '/static/assets'

		images:
			path: img         # path becomes '%wwwDir%/static/img'
			url: /images      # URL becomes '/images' (because of absolute path)

		cdn_styles:
			path: /var/www/shared/readonly       # this absolute path is used
			url: https://cdn.example.com/css/    # this absolute URL is used

		# Vite integration
		vite:
			type: vite                          # specifies ViteMapper
			manifestPath: dist/manifest.json    # path to Vite's manifest file
			baseUrl: /dist                      # base URL for Vite assets
			extension: [js, mjs, css]           # auto-extensions to try
			devServerUrl: http://localhost:5173 # URL to Vite dev server (optional)
			debug: %debugMode%                  # enable dev mode based on app's debug flag
```

 <!---->

Extension Autodetection
-----------------------

The mapper can automatically handle file extensions if the reference doesn't include one. You configure this using the `extension` option within a mapper's definition.

```neon
assets:
	mapping:
		# Always adds '.css', 'styles:main' becomes 'styles/main.css'
		styles:
			path: styles
			extension: css  # No leading dot

		# Tries '.svg' and then '.png' extensions, 'icons:myicon' becomes 'img/icons/myicon.svg' or 'img/icons/myicon.png'
		icons:
			path: img/icons
			extension: [svg, png] # Order matters

		# Adds '.js' if missing, both 'scripts:app' and 'scripts:app.js' become 'js/app.js'
		scripts:
			path: js
			extension: [js, ''] # Empty string allows matching without adding an extension
```

 <!---->

Using Custom Mappers
--------------------

By default, the configurations in the `mapping` section implicitly create instances of the built-in `Nette\Assets\FilesystemMapper`. If `FilesystemMapper` doesn't fit your needs (e.g., you need to load assets from a database, S3, or read a manifest file generated by build tools like Vite or Webpack), you can provide an instance of your own class implementing `Nette\Assets\Mapper`:

```neon
assets:
	mapping:
		# Uses a custom class instance directly
		products: App\Inventory\ProductImageMapper

		# Or references a service defined elsewhere in your configuration
		vite: @App\Build\ViteManifestMapper(%appDir%/../dist/.vite/manifest.json)
```

 <!---->

Retrieving Assets
-----------------

Retrieve assets via the `Registry` service, typically injected where needed. The main method is `getAsset()`:

```php
// Assume $assets is Nette\Assets\Registry obtained via dependency injection or service locator

// Option 1: Using getAsset() with try/catch for handling exceptions
$reference = 'images:logo.png'; // Or ['images', 'logo.png'], or just 'logo.png' for default mapper
try {
	$asset = $assets->getAsset($reference);
	echo $asset->url;
} catch (Nette\Assets\AssetNotFoundException $e) {
	// Handle asset not found situation
	echo 'Asset not found: ' . $e->getMessage();
}

// Option 2: Using tryGetAsset() for nullable return value
$asset = $assets->tryGetAsset($reference);
echo $asset?->url;
```

 <!---->

Usage in Latte
--------------

Assuming Latte helper `asset` is registered to call the `Registry`:

```latte
{* Using the default mapper (mapper identifier omitted) *}
<script src={asset('app.js')}></script>
<link rel="stylesheet" href={asset('style.css')}>

{* Specifying the mapper using the prefix *}
<audio src={asset('audio:podcast.mp3')}></audio>
<img src={asset('icons:logo')} alt="Logo">

{* Alternative syntax using an array *}
<audio src={asset(['audio', 'podcast.mp3'])}></audio>

{* Using tryAsset() for asset which may not exist *}
{var $asset = tryAsset('images:optional.jpg')}
<img n:if=$asset src=$asset alt="Optional Image">
```

The resulting URL string obtained from `$asset->url` or `{asset(...)}` will include versioning information if provided by the mapper.

 <!---->

Asset Versioning
----------------

The built-in `FilesystemMapper` supports asset versioning. By default, it automatically appends a version query parameter based on the file's last modification time (`filemtime`):

```latte
{asset('app.js')}
```

generates, for example:

```html
/assets/app.js?v=1699944800
```

This helps with browser cache invalidation. Custom mappers can implement different versioning strategies (e.g., using content hashes from a build manifest).

You can disable versioning per asset:

```php
$asset = $mapper->getAsset('app.js', ['versioning' => false]);
```

Or in configuration file:

```neon
assets:
	# Global versioning setting (defaults to true)
	versioning: false

	mapping:
		default:
			path: assets
			# Disable versioning for this mapper only
			versioning: false
```

 <!---->

Image Dimensions
----------------

When using `FilesystemMapper` (or any mapper returning a `FileAsset`), you can easily retrieve image dimensions (assuming corresponding Latte helpers are registered):

```latte
<img src={asset('images:logo.png')} width={asset('images:logo.png')->width} height={asset('images:logo.png')->height}>

{* alternative *}
{do $asset = asset('images:logo.png')}
<img src={$asset} width={$asset->width} height={$asset->height}>
```

`FileAsset` provides `$duration` property for estimating MP3 duration (most reliable for Constant Bitrate files).

 <!---->

Entry Points and Bundle Support
------------------------------

Nette Assets supports modern bundlers and build tools that generate multiple files from a single entry point:

- The `EntryAsset` class implements both `ScriptAsset` and `StyleAsset` interfaces
- It manages dependencies automatically and provides information about the main entry point
- The `renderAsset` Latte function automatically generates HTML tags for all related files

### Vite Integration

Vite integration automatically handles:
- Main entry points
- Dynamic imports (chunks)
- CSS extraction
- Development mode with HMR support

### Usage in Latte Templates

```latte
{* Renders all necessary script and link tags for this entry point *}
{renderAsset('vite:src/main.js')}
```
