<?php

declare(strict_types=1);

use Nette\Assets\ImageAsset;
use Nette\Assets\Registry;
use Nette\Assets\StyleAsset;
use Nette\Bridges\AssetsLatte\LatteExtension;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('n:asset', function () {
	$asset = new ImageAsset(
		url: 'https://example.com/image.jpg',
		width: 800,
		height: 600,
		alternative: 'Test image',
		lazyLoad: true,
	);

	$mockRegistry = Mockery::mock(Registry::class);
	$mockRegistry->expects()->getAsset('mapper:foo/bar.jpg', [])->andReturn($asset);

	$latte = new Latte\Engine;
	$latte->addExtension(new LatteExtension($mockRegistry));
	$latte->setLoader(new Latte\Loaders\StringLoader);

	$result = $latte->renderToString('<img n:asset="mapper:foo/bar.jpg">');
	Assert::same('<img src="https://example.com/image.jpg" width="800" height="600" alt="Test image" loading="lazy">', $result);
});


test('n:asset array syntax', function () {
	$asset = new ImageAsset('https://example.com/image.jpg');

	$mockRegistry = Mockery::mock(Registry::class);
	$mockRegistry->expects()->getAsset(['mapper', 'foo/bar.jpg'], [])->andReturn($asset);

	$latte = new Latte\Engine;
	$latte->addExtension(new LatteExtension($mockRegistry));
	$latte->setLoader(new Latte\Loaders\StringLoader);

	$result = $latte->renderToString('<img n:asset="[mapper, \'foo/bar.jpg\']">');
	Assert::same('<img src="https://example.com/image.jpg">', $result);
});


test('n:asset with options', function () {
	$mockRegistry = Mockery::mock(Registry::class);
	$mockRegistry->expects()
		->getAsset('foo', [123, 'foo' => 'bar'])
		->andReturn(new ImageAsset('https://example.com/image.jpg'));

	$latte = new Latte\Engine;
	$latte->addExtension(new LatteExtension($mockRegistry));
	$latte->setLoader(new Latte\Loaders\StringLoader);

	$result = $latte->renderToString('<img n:asset="foo, 123, foo: bar">');
	Assert::same('<img src="https://example.com/image.jpg">', $result);
});


test('n:asset with dimensions loading', function () {
	$asset = new ImageAsset(
		url: 'https://example.com/image.jpg',
		file: __DIR__ . '/fixtures/image.gif',
		density: 2,
	);

	$mockRegistry = Mockery::mock(Registry::class);
	$mockRegistry->expects()->getAsset('foo', [])->andReturn($asset);

	$latte = new Latte\Engine;
	$latte->addExtension(new LatteExtension($mockRegistry));
	$latte->setLoader(new Latte\Loaders\StringLoader);

	$result = $latte->renderToString('<img n:asset=foo>');
	Assert::same('<img src="https://example.com/image.jpg" width="88" height="52">', $result);
});


test('overwriting width / height', function () {
	$asset = new ImageAsset(
		url: 'https://example.com/image.jpg',
		width: 800,
		height: 600,
	);

	$mockRegistry = Mockery::mock(Registry::class);
	$mockRegistry->expects()->getAsset('foo', [])->andReturn($asset);

	$latte = new Latte\Engine;
	$latte->addExtension(new LatteExtension($mockRegistry));
	$latte->setLoader(new Latte\Loaders\StringLoader);

	$result = $latte->renderToString('<img n:asset=foo width=10 alt="foo">');
	Assert::same('<img src="https://example.com/image.jpg" height="8" width=10 alt="foo">', $result);

	$result = $latte->renderToString('<img n:asset=foo height=10 alt="foo">');
	Assert::same('<img src="https://example.com/image.jpg" width="13" height=10 alt="foo">', $result);

	$result = $latte->renderToString('<img n:asset=foo width={=10} alt="foo">');
	Assert::same('<img src="https://example.com/image.jpg" width="10" alt="foo">', $result);
});


test('not matching tag name', function () {
	$asset = new ImageAsset(
		url: 'https://example.com/image.jpg',
		width: 800,
		height: 600,
	);

	$mockRegistry = Mockery::mock(Registry::class);
	$mockRegistry->expects()->getAsset('foo', [])->andReturn($asset);

	$latte = new Latte\Engine;
	$latte->addExtension(new LatteExtension($mockRegistry));
	$latte->setLoader(new Latte\Loaders\StringLoader);

	Assert::exception(
		fn() => $latte->renderToString('<div n:asset=foo width=1 alt="foo"></div>'),
		Nette\InvalidArgumentException::class,
		'Tag <div> is not allowed for this asset. Use <img> instead.',
	);
});


test('stylesheet, not preloading mode', function () {
	$asset = new StyleAsset('style.css');

	$mockRegistry = Mockery::mock(Registry::class);
	$mockRegistry->expects()->getAsset('foo', [])->andReturn($asset);

	$latte = new Latte\Engine;
	$latte->addExtension(new LatteExtension($mockRegistry));
	$latte->setLoader(new Latte\Loaders\StringLoader);

	$result = $latte->renderToString('<link n:asset=foo>');
	Assert::same('<link rel="stylesheet" href="style.css">', $result);
});


test('preloading mode', function () {
	$asset = new ImageAsset(
		url: 'https://example.com/image.jpg',
		width: 800,
		height: 600,
	);

	$mockRegistry = Mockery::mock(Registry::class);
	$mockRegistry->expects()->getAsset('foo', [])->andReturn($asset);

	$latte = new Latte\Engine;
	$latte->addExtension(new LatteExtension($mockRegistry));
	$latte->setLoader(new Latte\Loaders\StringLoader);

	$result = $latte->renderToString('<link n:asset=foo>');
	Assert::same('<link rel="preload" href="https://example.com/image.jpg" as="image">', $result);
});


test('link', function () {
	$asset = new ImageAsset(
		url: 'https://example.com/image.jpg',
		width: 800,
		height: 600,
	);

	$mockRegistry = Mockery::mock(Registry::class);
	$mockRegistry->expects()->getAsset('foo', [])->andReturn($asset);

	$latte = new Latte\Engine;
	$latte->addExtension(new LatteExtension($mockRegistry));
	$latte->setLoader(new Latte\Loaders\StringLoader);

	$result = $latte->renderToString('<a n:asset=foo></a>');
	Assert::same('<a href="https://example.com/image.jpg"></a>', $result);
});
