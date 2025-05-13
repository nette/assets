# Nette Assets

[![Downloads this Month](https://img.shields.io/packagist/dm/nette/assets.svg)](https://packagist.org/packages/nette/assets)
[![Tests](https://github.com/nette/assets/workflows/Tests/badge.svg?branch=master)](https://github.com/nette/assets/actions)
[![Latest Stable Version](https://poser.pugx.org/nette/assets/v/stable)](https://github.com/nette/assets/releases)
[![License](https://img.shields.io/badge/license-New%20BSD-blue.svg)](https://github.com/nette/assets/blob/master/license.md)


### Whether you're building a simple website or complex application, Nette Assets makes working with static files a breeze.

✅ automatic file versioning for cache busting<br>
✅ smart file type detection<br>
✅ lazy loading of file properties (dimensions, duration)<br>
✅ clean API for PHP and Latte templates<br>
✅ support for multiple file sources<br>
✅ modern bundler integration (Vite)<br>


Working with static files (images, CSS, JavaScript) in web applications often involves repetitive tasks: generating correct URLs, handling cache invalidation, managing file versions, and dealing with different environments. Nette Assets simplifies all of this.

Without Nette Assets:
```latte
{* You need to manually handle paths and versioning *}
<img src="/images/logo.png?v=2" width="100" height="50">
<link rel="stylesheet" href="/css/style.css?v=1699123456">
```

With Nette Assets:
```latte
{* Everything is handled automatically *}
{asset 'images/logo.png'}
{asset 'css/style.css'}
```

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
{asset 'images/logo.png'}
```

This single line:
- Finds your image file
- Generates the correct URL with automatic versioning
- Outputs a complete `<img>` tag with proper dimensions

That's it! No configuration needed for basic usage. The library uses sensible defaults and works out of the box.


Custom HTML
-----------

Sometimes you need more control over the HTML:

```latte
{* Use n:asset when you want to control HTML attributes *}
<img n:asset="images/logo.png" alt="Company Logo" class="logo">
```

You can also get just the URL using the `asset()` function:

```latte
{* Get just the URL without HTML *}
<img src={asset('images/logo.png')} alt="Company Logo" class="logo">
```

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
{asset $logo}
{* or *}
<img n:asset=$logo>
{* or *}
<img src={$logo} width={$logo->width} height={$logo->height} alt="Logo">
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
echo $image->url;    // '/assets/photo.jpg?v=1699123456'
echo $image->width;  // 800
echo $image->height; // 600
```

Different file types have different properties. The library automatically detects the file type and creates the appropriate asset:

- **ImageAsset** - Images with width, height, alternative text, and lazy loading support
- **ScriptAsset** - JavaScript files with types and integrity hashes
- **StyleAsset** - CSS files with media queries
- **AudioAsset** - Audio files with duration information
- **VideoAsset** - Video files with dimensions, duration, poster image, and autoplay settings
- **EntryAsset** - Entry points with dependecies on scripts and styles
- **GenericAsset** - Generic files with mime types


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

// Uses the 'images' mapper
$photo = $assets->getAsset('images:photo.jpg');

// Alternative syntax
$photo = $assets->getAsset(['images', 'photo.jpg']);
```

 <!---->


Configuration
=============

While Nette Assets works with zero configuration, you can customize it to match your project structure.


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


Setting Base Paths
------------------

By default, if you don't specify base paths:
- `basePath` defaults to `%wwwDir%`
- `baseUrl` defaults to your project's base URL (e.g., `/`)

You can customize these to organize your static files under a common directory:

```neon
assets:
	basePath: %appDir%/static   # All mappers will be relative to this
	baseUrl: /static            # URL prefix for all assets

	mapping:
		default: css      # Files in %appDir%/static/css, URLs like /static/css/...
		images: img       # Files in %appDir%/static/img, URLs like /static/img/...
		scripts: js       # Files in %appDir%/static/js, URLs like /static/js/...
```


Advanced Configuration
----------------------

For more control, you can configure each mapper in detail:

```neon
assets:
	mapping:
		# Simple format
		images: img

		# Detailed format
		styles:
			path: css                   # Where to find files
			extension: css              # Always add .css extension

		# Different URL and directory path
		audio:
			path: audio
			url: https://static.example.com/audio

		# Custom mapper for external files
		cdn: @cdnMapper
```

The `path` and `url` can be:
- **Relative**: resolved from `%wwwDir%` (or `basePath`), ie. project base URL (or `baseUrl`)
- **Absolute**: used as-is (`/var/www/shared/assets`)

 <!---->


Working with Assets
===================

Let's explore how to work with assets in your PHP code.


Basic Retrieval
---------------

The Registry provides two methods for getting assets:

```php
// This throws AssetNotFoundException if file doesn't exist
try {
	$logo = $assets->getAsset('images/logo.png');
	echo $logo->url;
} catch (AssetNotFoundException $e) {
	// Handle missing asset
}

// This returns null if file doesn't exist
$banner = $assets->tryGetAsset('images/banner.jpg');
if ($banner) {
	echo $banner->url;
}
```


Specifying Mappers
------------------

You can explicitly choose which mapper to use:

```php
// Use default mapper
$asset = $assets->getAsset('document.pdf');

// Use specific mapper (prefix with colon)
$asset = $assets->getAsset('images:logo.png');

// Alternative array syntax
$asset = $assets->getAsset(['images', 'logo.png']);
```


Asset Types and Properties
--------------------------


The library automatically detects file types and provides relevant properties:

```php
// Images
$image = $assets->getAsset('photo.jpg');
echo $image->width;   // 1920
echo $image->height;  // 1080
echo $image->url;     // '/assets/photo.jpg?v=1699123456'

// Audio files (MP3)
$audio = $assets->getAsset('episode-01.mp3');
echo $audio->duration;  // 3600.5 (seconds)

// All assets can be cast to string (returns URL)
$url = (string) $assets->getAsset('document.pdf');
```


Lazy Loading of Properties
--------------------------

Properties like image dimensions, audio duration, or MIME types are retrieved only when accessed. This keeps the library fast:

```php
$image = $assets->getAsset('large-photo.jpg');
// No file operations yet

echo $image->url;  // Just returns URL, no file reading

echo $image->width;  // NOW it reads the file header to get dimensions
echo $image->height; // Already loaded, no additional file reading

// Even generic assets lazy-load their MIME type
$file = $assets->getAsset('document.pdf');
echo $file->mimeType; // Now it detects: 'application/pdf'
```


Working with Options
--------------------

Mappers can support additional options to control their behavior. For example, the `FilesystemMapper` supports the `version` option:

```php
// Disable versioning for specific asset
$asset = $assets->getAsset('style.css', ['version' => false]);
echo $asset->url;  // '/assets/style.css' (no ?v=... parameter)
```

Different mappers may support different options. Custom mappers can define their own options to provide additional functionality.

 <!---->


Latte Integration
=================

Nette Assets shines in Latte templates with intuitive tags and functions.


Basic Usage with `{asset}` Tag
------------------------------

The `{asset}` tag renders complete HTML elements:

```latte
{* Renders: <img src="/assets/hero.jpg?v=123" width="1920" height="1080"> *}
{asset 'images/hero.jpg'}

{* Renders: <script src="/assets/app.js?v=456"></script> *}
{asset 'scripts/app.js'}

{* Renders: <link rel="stylesheet" href="/assets/style.css?v=789"> *}
{asset 'styles/style.css'}

{* Any additional parameters are passed as asset options *}
{asset 'style.css', version: false}
```

The tag automatically:
- Detects the asset type from file extension
- Generates the appropriate HTML element
- Adds versioning for cache busting
- Includes dimensions for images


Using Specific Mappers
----------------------

Just like in PHP, you can specify which mapper to use:

```latte
{* Uses the 'images' mapper *}
{asset 'images:product-photo.jpg'}

{* Alternative array syntax *}
{asset ['images', 'product-photo.jpg']}
```


Custom HTML with `n:asset` Attribute
------------------------------------

When you need control over the HTML attributes:

```latte
{* The n:asset attribute fills in the appropriate attributes *}
<img n:asset="images:product.jpg" alt="Product Photo" class="rounded shadow">

{* Works with any relevant HTML element *}
<script n:asset="scripts/analytics.js" defer></script>

{* You can pass options for assets *}
<link n:asset="styles/print.css, version: false" media="print">
```

The `n:asset` attribute:
- Sets `src` for images, scripts, and audio/video
- Sets `href` for stylesheets and preload links
- Adds dimensions for images and other attributes
- Preserves all your custom attributes


Getting Just URLs with Functions
--------------------------------

For maximum flexibility, use the `asset()` function:

```latte
{* Get the URL only *}
<div style="background-image: url('{asset('images/bg.jpg')}')">
	Content
</div>

{* Access asset properties *}
{var $logo = asset('images/logo.png')}
<img
	src={$logo}
	width={$logo->width}
	height={$logo->height}
	alt="Logo"
	srcset="{asset('images/logo@2x.png')} 2x"
>
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
<img n:asset="tryAsset('user-avatar.jpg') ?? asset('default-avatar.jpg')" alt="Avatar">
```


Performance Optimization with Preloading
----------------------------------------


Improve page load performance by preloading critical assets:

```latte
{* In your <head> section *}
{preload 'styles/critical.css'}
{preload 'fonts/heading.woff2'}
```

Generates:

```html
<link rel="preload" href="/assets/styles/critical.css?v=123" as="style">
<link rel="preload" href="/assets/fonts/heading.woff2" as="font" crossorigin>
```

The `{preload}` tag automatically:
- Determines the correct `as` attribute
- Adds `crossorigin` for fonts
- Uses `modulepreload` for ES modules

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

You can also make extensions optional:

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

You can disable versioning at multiple levels:

```neon
assets:
	# Global versioning setting (defaults to true)
	versioning: false

	mapping:
		default:
			path: assets
			# Enable versioning for this mapper only
			versioning: true
```

Or per asset using asset options:

```php
// In PHP
$asset = $assets->getAsset('style.css', ['version' => false]);

// In Latte
{asset 'style.css', version: false}
```


Media Properties
----------------

The `FilesystemMapper` automatically extracts useful properties from media files.

Image dimensions are loaded lazily when accessed:

```latte
{var $hero = asset('images:hero-image.jpg')}
<img
	src={$hero}
	width={$hero->width}
	height={$hero->height}
	alt="Hero Image"
	loading="lazy"
>
```

For MP3 files, duration is estimated (most accurate for Constant Bitrate files):

```latte
{var $audio = asset('audio:episode-01.mp3')}
<audio controls>
	<source src={$audio} type={$audio->mimeType}>
	Your browser doesn't support audio playback.
</audio>
<p>Duration: {$audio->duration|round} seconds</p>
```

Video files provide dimensions and can work with poster images:

```latte
{var $video = asset('videos:intro.mp4')}
<video
	width={$video->width}
	height={$video->height}
	poster={asset('videos:intro-poster.jpg')}
	controls>
	<source src={$video} type={$video->mimeType}>
</video>
```


Working with Fonts
------------------

Font assets support preloading with proper CORS attributes:

```latte
{* Generates proper preload with crossorigin attribute *}
{preload 'fonts:OpenSans-Regular.woff2'}

{* In your CSS *}
<style>
	@font-face {
		font-family: 'Open Sans';
		src: url('{asset('fonts:OpenSans-Regular.woff2')}') format('woff2');
		font-display: swap;
	}
</style>
```

 <!---->


Vite Integration
================

For modern JavaScript applications, Nette Assets includes a specialized `ViteMapper` that integrates with Vite's build process.


Basic Setup
-----------

Vite integration requires [enabling the build manifest](https://vite.dev/config/build-options#build-manifest) in your `vite.config.ts`:

```js
export default {
	build: {
		manifest: true  // This is required!
	}
}
```

Configure the ViteMapper in your NEON file:

```neon
assets:
	mapping:
		vite:
			type: vite
			path: build                       # Where Vite outputs files
			devServer: http://localhost:5173  # Dev server URL
```

If you use a custom manifest path in Vite:

```js
export default {
	build: {
		manifest: 'assets-manifest.json'  // Custom manifest filename
	}
}
```

Specify the same path in your NEON configuration:

```neon
assets:
	mapping:
		vite:
			type: vite
			path: build
			manifest: build/assets-manifest.json  # Match your Vite config
```


Development vs Production
-------------------------

The ViteMapper automatically switches between development and production modes:

**During Development:**

```latte
{* Serves from Vite dev server with hot module replacement *}
{asset 'vite:src/main.js'}
{* Output: <script src="http://localhost:5173/src/main.js" type="module"></script> *}
```

**In Production:**

```latte
{* Serves built files with hashed names *}
{asset 'vite:src/main.js'}
{* Output: <script src="/build/assets/main-4a8f9c7.js" type="module"></script> *}
```


Understanding Entry Points and Dependencies
-------------------------------------------

When you build a modern JavaScript application, your bundler (like Vite) often splits code into multiple files for better performance. An "entry point" is your main JavaScript file that imports other modules.

For example, your `src/main.js` might:
- Import a CSS file
- Import vendor libraries (like Vue or React)
- Import your application components

Vite processes this and generates:
- The main JavaScript file
- Extracted CSS file(s)
- Vendor chunks for better caching
- Dynamic imports for code splitting

The `EntryAsset` class handles this complexity:

```php
$app = $assets->getAsset('vite:src/main.js');
// Returns an EntryAsset with:
// - url: The main JavaScript file
// - dependencies: Array of CSS files and JS chunks
```


Rendering Entry Points
----------------------

The `{asset}` tag automatically handles all dependencies. You can reference entry points in two ways:

**By file path**: Use the actual source file path:

```latte
{asset 'vite:src/main.js'}
```

**By entry name**: Use the name defined in your Vite config's [rollupOptions.input](https://rollupjs.org/configuration-options/#input):

```latte
{asset 'vite:main'}
{asset 'vite:admin'}
```

Each `{asset}` tag renders everything needed for that entry point:

```html
<!-- Main entry point -->
<script src="/build/assets/main-4a8f9c7.js" type="module"></script>

<!-- Extracted CSS -->
<link rel="stylesheet" href="/build/assets/main-2b9c8d7.css">

<!-- Vendor chunk preload -->
<link rel="modulepreload" href="/build/assets/vendor-8c7fa9b.js">
```

Large applications often have multiple entry points with shared dependencies. Vite automatically deduplicates shared chunks.


Versioning in Vite
------------------

Unlike `FilesystemMapper`, Vite handles versioning by including a hash in the filename itself (`main-4a8f9c7.js`). This approach works better with JavaScript module imports.


Fallback to Filesystem
---------------------

If a file isn't found in the Vite manifest, `ViteMapper` will attempt to find it directly on the filesystem, similar to how `FilesystemMapper` works. This is particularly useful for files in Vite's `publicDir` (default: `public/`), which are copied as-is to the output directory without being processed or included in the manifest.


Code Splitting and Dynamic Imports
----------------------------------

When your application uses dynamic imports:

```js
// In your JavaScript
if (condition) {
	import('./features/special-feature.js').then(module => {
		module.init();
	});
}
```

Nette Assets does **not** automatically preload dynamic imports - this is intentional as preloading all possible dynamic imports could hurt performance.

If you want to preload specific dynamic imports, you can do so explicitly:

```latte
{* Manually preload critical dynamic imports *}
{preload 'vite:features/special-feature.js'}
```

This gives you fine-grained control over which resources are preloaded based on your application's needs.

 <!---->


Creating Custom Mappers
=======================

While the built-in `FilesystemMapper` and `ViteMapper` handle most use cases, you might need custom asset resolution for:
- Cloud storage (S3, Google Cloud)
- Database-stored files
- Dynamic asset generation
- Third-party CDN integration


The Mapper Interface
--------------------

All mappers implement a simple interface:

```php
interface Mapper
{
	/**
	 * @throws AssetNotFoundException
	 */
	public function getAsset(string $reference, array $options = []): Asset;
}
```

The contract is straightforward:
- Take a reference (like "logo.png" or "reports/annual-2024.pdf")
- Return an Asset object
- Throw `AssetNotFoundException` if the asset doesn't exist


Database Mapper Example
-----------------------

For applications storing file metadata in a database:

```php
class DatabaseMapper implements Mapper
{
	public function __construct(
		private Connection $db,
		private string $baseUrl,
		private Storage $storage,
	) {}

	public function getAsset(string $reference, array $options = []): Asset
	{
		// Find asset in database
		$row = $this->db->fetchRow('SELECT * FROM assets WHERE id = ?', $reference);
		if (!$row) {
			throw new AssetNotFoundException("Asset '$reference' not found in database");
		}

		// Build URL
		$url = $this->baseUrl . '/file/' . $row->storage_path;

		// Get local path if available
		$localPath = $this->storage->getLocalPath($row->storage_path);

		return Helpers::createAssetFromUrl(
			url: $url,
			path: $localPath,
			args: [
				'mimeType' => $row->mime_type,
				'width' => $row->width,
				'height' => $row->height,
			]
		);
	}
}
```

Register in configuration:

```neon
assets:
	mapping:
		db: DatabaseMapper(...)
```


Cloud Storage Mapper
--------------------

For S3 or Google Cloud Storage:

```php
class S3Mapper implements Mapper
{
	public function __construct(
		private S3Client $s3,
		private string $bucket,
		private string $region,
		private bool $private = false
	) {}

	public function getAsset(string $reference, array $options = []): Asset
	{
		try {
			// Check if object exists
			$this->s3->headObject([
				'Bucket' => $this->bucket,
				'Key' => $reference,
			]);

			if ($this->private) {
				// Generate presigned URL for private files
				$url = $this->s3->createPresignedRequest(
					$this->s3->getCommand('GetObject', [
						'Bucket' => $this->bucket,
						'Key' => $reference,
					]),
					'+10 minutes'
				)->getUri();
			} else {
				// Public URL
				$url = "https://s3.{$this->region}.amazonaws.com/{$this->bucket}/{$reference}";
			}

			return Helpers::createAssetFromUrl($url, null);

		} catch (S3Exception $e) {
			throw new AssetNotFoundException("Asset '$reference' not found in S3");
		}
	}
}
```


Using Options
-------------

Options allow users to modify mapper behavior on a per-asset basis. This is useful when you need different transformations, sizes, or processing for the same asset:

```php
public function getAsset(string $reference, array $options = []): Asset
{
	$thumbnail = $options['thumbnail'] ?? null;

	$url = $thumbnail
		? $this->cdnUrl . '/thumb/' . $reference
		: $this->cdnUrl . '/' . $reference;

	return Helpers::createAssetFromUrl($url, null);
}
```

Usage:

```php
// Get normal image
$photo = $assets->getAsset('cdn:photo.jpg');

// Get thumbnail version
$thumbnail = $assets->getAsset('cdn:photo.jpg', ['thumbnail' => true]);

// In Latte: {asset 'cdn:photo.jpg', thumbnail: true}
```

This pattern is useful for:
- Image transformations (thumbnails, different sizes)
- CDN parameters (quality, format conversion)
- Access control (signed URLs, expiration times)


Handle Multiple Sources
-----------------------

Sometimes you need to check multiple locations for an asset. A fallback mapper can try different sources in order:

```php
class FallbackMapper implements Mapper
{
	public function __construct(
		private array $mappers
	) {}

	public function getAsset(string $reference, array $options = []): Asset
	{
		foreach ($this->mappers as $mapper) {
			try {
				return $mapper->getAsset($reference, $options);
			} catch (AssetNotFoundException) {
				// continue
			}
		}

		throw new AssetNotFoundException("Asset '$reference' not found in any source");
	}
}
```

This is useful for:
- **Progressive migration**: Check new storage first, fall back to old
- **Multi-tier storage**: Fast cache → slower database → external API
- **Redundancy**: Primary CDN → backup CDN → local files
- **Environment-specific sources**: Local files in development, S3 in production

Example configuration:

```neon
assets:
	mapping:
		fallback: FallbackMapper([
		@cacheMapper,      # Try fast cache first
		@databaseMapper,   # Then database
		@filesystemMapper  # Finally, local files
	])
```

These advanced features make custom mappers extremely flexible and capable of handling complex asset management scenarios while maintaining the simple, consistent API that Nette Assets provides.


Best Practices for Custom Mappers
---------------------------------

1. **Always throw `AssetNotFoundException`** with a descriptive message when an asset can't be found
2. **Use `Helpers::createAssetFromUrl()`** to create the correct asset type based on file extension
3. **Support the `$options` parameter** for flexibility, even if you don't use it initially
4. **Document reference formats** clearly (e.g., "Use 'folder/file.ext' or 'uuid'")
5. **Consider caching** if asset resolution involves network requests or database queries
6. **Handle errors gracefully** and provide meaningful error messages
7. **Test edge cases** like missing files, network errors, and invalid references

With custom mappers, Nette Assets can integrate with any storage system while maintaining a consistent API across your application.


[Support Me](https://github.com/sponsors/dg)
============================================

Do you like Nette Caching? Are you looking forward to the new features?

[![Buy me a coffee](https://files.nette.org/icons/donation-3.svg)](https://github.com/sponsors/dg)

Thank you!
