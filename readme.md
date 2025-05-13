# Nette Assets

[![Downloads this Month](https://img.shields.io/packagist/dm/nette/assets.svg)](https://packagist.org/packages/nette/assets)
[![Tests](https://github.com/nette/assets/workflows/Tests/badge.svg?branch=master)](https://github.com/nette/assets/actions)
[![Latest Stable Version](https://poser.pugx.org/nette/assets/v/stable)](https://github.com/nette/assets/releases)
[![License](https://img.shields.io/badge/license-New%20BSD-blue.svg)](https://github.com/nette/assets/blob/master/license.md)


### Whether you're building a simple website or complex application, Nette Assets makes working with static files a breeze.

✅ automatic file versioning for cache busting<br>
✅ smart file type detection<br>
✅ lazy loading of file properties (dimensions, duration)<br>
✅ clean API for PHP and [Latte](https://latte.nette.org) templates<br>
✅ support for multiple file sources<br>


 <!---->


Installation
============

Install via Composer:

```shell
composer require nette/assets
```

Requirements: PHP 8.1 or higher.

 <!---->


Quick Start
===========

Let's start with the simplest possible example. You want to display an image in your application:

```latte
{* In your Latte template *}
<img src={asset('images/logo.png')} alt="Company Logo" class="logo">
```

This single line:
- Finds your image file
- Generates the correct URL with automatic versioning

That's it! No configuration needed for basic usage. The library uses sensible defaults and works out of the box.


Using in PHP
------------

In your presenters or services:

```php
public function __construct(
	private Nette\Assets\Registry $assets
) {}

public function renderDefault(): void
{
	$logo = $this->assets->getAsset('images/logo.png');
	$this->template->logo = $logo;
}
```

Then in your template:

```latte
<img src={$logo} width={$logo->getWidth()} height={$logo->getHeight()} alt="Logo">
```

 <!---->


Basic Concepts
==============

Before diving deeper, let's understand three simple concepts that make Nette Assets powerful yet easy to use.


What is an Asset?
-----------------

An asset is any static file in your application - images, stylesheets, scripts, fonts, etc. In Nette Assets, each file becomes an `Asset` object with useful properties:

```php
$image = $assets->getAsset('photo.jpg');
echo $image->getUrl();    // '/assets/photo.jpg?v=1699123456'
echo $image->getWidth();  // 800
echo $image->getHeight(); // 600
```


Where Assets Come From (Mappers)
--------------------------------

A mapper is a service that knows how to find files and create URLs for them. The built-in `FilesystemMapper` does two things:
1. Looks for files in a specified directory
2. Generates public URLs for those files

You can have multiple mappers for different purposes:


The Registry - Your Main Entry Point
------------------------------------

The Registry manages all your mappers and provides a simple API to get assets:

```php
// Inject the registry
public function __construct(
	private Nette\Assets\Registry $assets
) {}

// Use it to get assets
$logo = $this->assets->getAsset('images/logo.png');
```

The registry is smart about which mapper to use:

```php
// Uses the 'default' mapper
$css = $assets->getAsset('style.css');

// Uses the 'images' mapper (using prefix)
$photo = $assets->getAsset('images:photo.jpg');

// Uses the 'images' mapper (using array)
$photo = $assets->getAsset(['images', 'photo.jpg']);
```

 <!---->


Configuration
=============

While Nette Assets works with zero configuration, you can customize it to match your project structure.


Zero Configuration
------------------

Without any configuration, Nette Assets expects all your static files to be in the `assets` folder within your public directory:

```
www/
├── assets/
│   └── logo.png
└── index.php
```

This creates a default mapper that:
- Looks for files in `%wwwDir%/assets`
- Generates URLs like `/assets/file.ext`


Minimal Configuration
---------------------

The simplest [configuration](https://doc.nette.org/en/configuring) just tells the library where to find files:

```neon
assets:
	mapping:
		# This creates a filesystem mapper that:
		# - looks for files in %wwwDir%/assets
		# - generates URLs like /assets/file.ext
		default: assets
```

This is equivalent to the zero configuration setup but makes it explicit.


Setting Base Paths
------------------

By default, if you don't specify base paths:
- `path` defaults to `%wwwDir%`
- `url` defaults to your project's base URL (e.g., `/`)

You can customize these to organize your static files under a common directory:

```neon
assets:
	# All mappers will resolve paths relative to this directory
	basePath: %wwwDir%/static

	# All mappers will resolve URL relative to this
	baseUrl: /static

	mapping:
		# Files in %wwwDir%/static/img, URLs like /static/img/photo.jpg
		default: img

		# Files in %wwwDir%/static/js, URLs like /static/js/app.js
		scripts: js
```


Advanced Configuration
----------------------

For more control, you can configure each mapper in detail:

```neon
assets:
	mapping:
		# Simple format - creates FilesystemMapper looking in 'img' folder
		images: img

		# Detailed format with additional options
		styles:
			path: css                   # Directory to search for files
			extension: css              # Always add .css extension to requests

		# Different URL and directory path
		audio:
			path: audio                 # Files stored in 'audio' directory
			url: https://static.example.com/audio  # But served from CDN

		# Custom mapper service (dependency injection)
		cdn: @cdnMapper
```

The `path` and `url` can be:
- **Relative**: resolved from `%wwwDir%` (or `path`) and project base URL (or `url`)
- **Absolute**: used as-is (`/var/www/shared/assets`, `https://cdn.example.com`)


Manual Configuration (Without Nette Framework)
----------------------------------------------

If you're not using the Nette Framework or prefer to configure everything manually in PHP:

```php
use Nette\Assets\Registry;
use Nette\Assets\FilesystemMapper;

// Create registry
$registry = new Registry;

// Add mappers manually
$registry->addMapper('default', new FilesystemMapper(
	baseUrl: 'https://example.com/assets',   // URL prefix
	basePath: __DIR__ . '/assets',           // Filesystem path
	extensions: ['webp', 'jpg', 'png'],      // Try WebP first, fallback to JPG/PNG
	versioning: true
));

// Use the registry
$logo = $registry->getAsset('logo');  // Finds logo.webp, logo.jpg, or logo.png
echo $logo->url;
```

For more advanced configuration options using NEON format without the full Nette Framework, install the configuration component:

```shell
composer require nette/bootstrap
```

Then you can use NEON configuration files as described in the [Nette Bootstrap documentation](https://doc.nette.org/en/bootstrap).

 <!---->


Working with Assets
===================

Let's explore how to work with assets in your PHP code.


Basic Retrieval
---------------

The Registry provides two methods for getting assets:

```php
// This throws Nette\Assets\AssetNotFoundException if file doesn't exist
try {
	$logo = $assets->getAsset('images/logo.png');
	echo $logo->url;
} catch (AssetNotFoundException $e) {
	// Handle missing asset
}

// This returns null if file doesn't exist
$banner = $assets->tryGetAsset('images/banner.jpg');
if ($banner) {
	echo $banner->getUrl();
}
```


Specifying Mappers
------------------

You can explicitly choose which mapper to use:

```php
// Use default mapper
$asset = $assets->getAsset('document.pdf');

// Use specific mapper using prefix with colon
$asset = $assets->getAsset('images:logo.png');

// Use specific mapper using array syntax
$asset = $assets->getAsset(['images', 'logo.png']);
```


Asset Types and Properties
--------------------------

The library automatically detects file types and provides relevant properties:

```php
// Images
$image = $assets->getAsset('photo.jpg');
echo $image->getWidth();   // 1920
echo $image->getHeight();  // 1080
echo $image->getUrl();     // '/assets/photo.jpg?v=1699123456'

// All assets can be cast to string (returns URL)
$url = (string) $assets->getAsset('document.pdf');
```


Lazy Loading of Properties
--------------------------

Properties like image dimensions, audio duration, or MIME types are retrieved only when accessed. This keeps the library fast:

```php
$image = $assets->getAsset('photo.jpg');
// No file operations yet

echo $image->getUrl();  // Just returns URL, no file reading

echo $image->getWidth();  // NOW it reads the file header to get dimensions
echo $image->getHeight(); // Already loaded, no additional file reading

// For MP3 files, duration is estimated (most accurate for Constant Bitrate files)
$audio = asset('audio:episode-01.mp3');
echo $audio->getDuration(); // in seconds
```


Working with Options
--------------------

Mappers can support additional options to control their behavior. Different mappers may support different options. Custom mappers can define their own options to provide additional functionality.

 <!---->


Latte Integration
=================

Nette Assets shines in [Latte templates](https://latte.nette.org) with intuitive tags and functions.

```latte
{var $logo = asset('images/logo.png')}
<img src={$logo} width={$logo->getWidth()} height={$logo->getHeight()}>
```


Handling Optional Assets
------------------------

For assets that might not exist:

```latte
{* Using tryAsset() function *}
{var $banner = tryAsset('images/summer-sale.jpg')}
{if $banner}
	<div class="banner">
		<img src={$banner} alt="Summer Sale">
	</div>
{/if}

{* Or with a fallback *}
<img src={tryAsset('user-avatar.jpg') ?? asset('default-avatar.jpg')} alt="Avatar">
```

 <!---->


Advanced Features
=================


Extension Autodetection
-----------------------

When you have multiple formats of the same asset, the built-in `FilesystemMapper` can automatically find the right one:

```neon
assets:
	mapping:
		images:
			path: img
			extension: [webp, jpg, png]  # Check for each extension in order
```

Now when you request an asset without extension:

```latte
{* Automatically finds: logo.webp, logo.jpg, or logo.png *}
{asset 'images:logo'}
```

This is useful for:
- Progressive enhancement (WebP with JPEG fallback)
- Flexible asset management
- Simplified templates

You can also make extension optional:

```neon
assets:
	mapping:
		scripts:
			path: js
			extension: [js, '']  # Try with .js first, then without
```


Asset Versioning
----------------

Browser caching is great for performance, but it can prevent users from seeing updates. Asset versioning solves this problem.

The `FilesystemMapper` automatically adds version parameters based on file modification time:

```latte
{asset 'css/style.css'}
{* Output: <link rel="stylesheet" href="/css/style.css?v=1699123456"> *}
```

When you update the CSS file, the timestamp changes, forcing browsers to download the new version.


 <!---->


[Support Me](https://github.com/sponsors/dg)
============================================

Do you like Nette Caching? Are you looking forward to the new features?

[![Buy me a coffee](https://files.nette.org/icons/donation-3.svg)](https://github.com/sponsors/dg)

Thank you!
