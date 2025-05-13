<?php

declare(strict_types=1);

use Nette\Assets\ImageAsset;
use Nette\Assets\Registry;
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
