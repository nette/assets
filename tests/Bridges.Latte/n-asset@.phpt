<?php

declare(strict_types=1);

use Nette\Assets\ImageAsset;
use Nette\Assets\Registry;
use Nette\Bridges\AssetsLatte\LatteExtension;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('n:asset?', function () {
	$asset = new ImageAsset(
		url: 'https://example.com/image.jpg',
		width: 800,
		height: 600,
		alternative: 'Test image',
		lazyLoad: true,
	);

	$mockRegistry = Mockery::mock(Registry::class);
	$mockRegistry->expects()->tryGetAsset('foo', [])->andReturn($asset);

	$latte = new Latte\Engine;
	$latte->addExtension(new LatteExtension($mockRegistry));
	$latte->setLoader(new Latte\Loaders\StringLoader);

	$result = $latte->renderToString('<img n:asset?=foo>');
	Assert::same('<img src="https://example.com/image.jpg" width="800" height="600" alt="Test image" loading="lazy">', $result);
});


test('n:asset? when asset is missing', function () {
	$mockRegistry = Mockery::mock(Registry::class);
	$mockRegistry->expects()->tryGetAsset('foo', [])->andReturn(null);

	$latte = new Latte\Engine;
	$latte->addExtension(new LatteExtension($mockRegistry));
	$latte->setLoader(new Latte\Loaders\StringLoader);

	$result = $latte->renderToString('<img n:asset?=foo>');
	Assert::same('', $result);
});
