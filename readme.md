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
✅ verify asset existence in development mode<br>
✅ support multiple storage backends

The library provides a clean and intuitive API to manage static assets in your web applications with focus on developer experience and performance.

 <!---->

Installation and Requirements
-----------------------------

The recommended way to install is via Composer:

```shell
composer require nette/assets
```

Nette Assets requires PHP 8.1 or higher.

 <!---->

Usage
-----

First, configure your assets in your application's configuration file:

```neon
assets:
	mapping:
		default: assets       # maps 'default:' prefix to /assets directory
		audio: media/audio    # maps 'audio:' prefix to /media/audio directory
```

Then use assets in your Latte templates:

```latte
<script src={asset('app.js')} defer></script>
```

You can also use mapper-specific prefixes:

```latte
<audio src={asset('audio:podcast.mp3')} controls></audio>
```

 <!---->

Asset Versioning
----------------

The library automatically appends version query string to asset URLs based on file modification time:

```latte
{asset('app.js')}
```

generates for example:

```html
/assets/app.js?v=1699944800
```

This ensures proper cache invalidation when assets change.

 <!---->

Image Dimensions
----------------

Get image dimensions easily in templates:

```latte
<img src={asset('logo.png')} width={assetWidth('logo.png')} height={assetHeight('logo.png')}>
```

 <!---->

Multiple Storage Backends
-------------------------

The library supports multiple mappers, which can be configured independently:

```neon
assets:
	mapping:
		product: App\UI\Accessory\ProductMapper(https://img.example.com, %rootDir%/www.img)
```
