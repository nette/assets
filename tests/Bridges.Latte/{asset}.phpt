<?php

declare(strict_types=1);

use Nette\Assets\EntryAsset;
use Nette\Assets\ImageAsset;
use Nette\Assets\Registry;
use Nette\Assets\ScriptAsset;
use Nette\Assets\StyleAsset;
use Nette\Bridges\AssetsLatte\LatteExtension;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('{asset} renders HTML', function () {
	$asset = new ImageAsset(
		url: 'https://example.com/image.jpg',
		width: 800,
		height: 600,
		alternative: 'Test image',
		lazyLoad: true,
	);

	$mockRegistry = Mockery::mock(Registry::class);
	$mockRegistry->expects()->getAsset('foo', [])->andReturn($asset);

	$latte = new Latte\Engine;
	$latte->addExtension(new LatteExtension($mockRegistry));
	$latte->setLoader(new Latte\Loaders\StringLoader);

	$result = $latte->renderToString('{asset foo}');
	Assert::same('<img src="https://example.com/image.jpg" width="800" height="600" alt="Test image" loading="lazy">', $result);
});


test('{asset} inside HTML attribute', function () {
	$asset = new ImageAsset('https://example.com/image.jpg');

	$mockRegistry = Mockery::mock(Registry::class);
	$mockRegistry->expects()->getAsset('foo', [])->andReturn($asset);

	$latte = new Latte\Engine;
	$latte->addExtension(new LatteExtension($mockRegistry));
	$latte->setLoader(new Latte\Loaders\StringLoader);

	$result = $latte->renderToString('<img src={asset foo}>');
	Assert::same('<img src="https://example.com/image.jpg">', $result);
});


test('{asset} with options', function () {
	$mockRegistry = Mockery::mock(Registry::class);
	$mockRegistry->expects()
		->getAsset('foo', [123, 'foo' => 'bar'])
		->andReturn(new ImageAsset('https://example.com/image.jpg'));

	$latte = new Latte\Engine;
	$latte->addExtension(new LatteExtension($mockRegistry));
	$latte->setLoader(new Latte\Loaders\StringLoader);

	$result = $latte->renderToString('{asset foo, 123, foo: bar}');
	Assert::same('<img src="https://example.com/image.jpg">', $result);
});


test('{asset} renders all dependencies of EntryAsset', function () {
	$asset = new EntryAsset(
		url: 'https://example.com/assets/main-1a2b3c4d.js',
		preloads: [
			new ScriptAsset(
				url: 'https://example.com/assets/shared-5e6f7g8h.js',
				integrity: 'sha384-hash123',
			),
		],
		imports: [
			new StyleAsset(
				url: 'https://example.com/assets/main-a1b2c3d4.css',
				media: 'screen',
			),
		],
		file: '/path/to/assets/main-1a2b3c4d.js',
	);

	$mockRegistry = Mockery::mock(Registry::class);
	$mockRegistry->expects()
		->getAsset('foo', [])
		->andReturn($asset);

	$latte = new Latte\Engine;
	$latte->addExtension(new LatteExtension($mockRegistry));
	$latte->setLoader(new Latte\Loaders\StringLoader);

	$result = $latte->renderToString('{asset foo}');
	Assert::same('<script src="https://example.com/assets/main-1a2b3c4d.js" type="module"></script>'
		. '<link rel="preload" href="https://example.com/assets/shared-5e6f7g8h.js" as="script">'
		. '<link rel="stylesheet" href="https://example.com/assets/main-a1b2c3d4.css" media="screen">', $result);
});
