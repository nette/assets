<?php declare(strict_types=1);

use Nette\Assets\ImageAsset;
use Nette\Assets\Registry;
use Nette\Bridges\AssetsLatte\LatteExtension;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('n:class before n:asset on the same element does not clobber the resolved asset', function () {
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

	$result = $latte->renderToString('<img n:class="\'foo\', \'bar\'" n:asset=foo>');
	Assert::same('<img class="foo bar" src="https://example.com/image.jpg" width="800" height="600">', $result);
});


test('n:asset before n:class on the same element does not clobber the resolved asset', function () {
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

	$result = $latte->renderToString('<img n:asset=foo n:class="\'foo\', \'bar\'">');
	Assert::same('<img src="https://example.com/image.jpg" width="800" height="600" class="foo bar">', $result);
});


test('multiple n:asset + n:class elements in one template each keep their own asset', function () {
	$first = new ImageAsset(url: 'https://example.com/first.jpg', width: 100, height: 100);
	$second = new ImageAsset(url: 'https://example.com/second.jpg', width: 200, height: 200);

	$mockRegistry = Mockery::mock(Registry::class);
	$mockRegistry->expects()->getAsset('first', [])->andReturn($first);
	$mockRegistry->expects()->getAsset('second', [])->andReturn($second);

	$latte = new Latte\Engine;
	$latte->addExtension(new LatteExtension($mockRegistry));
	$latte->setLoader(new Latte\Loaders\StringLoader);

	$result = $latte->renderToString(
		'<img n:class="\'a\'" n:asset=first><img n:class="\'b\'" n:asset=second>',
	);
	Assert::same(
		'<img class="a" src="https://example.com/first.jpg" width="100" height="100">'
		. '<img class="b" src="https://example.com/second.jpg" width="200" height="200">',
		$result,
	);
});


test('n:asset? combined with n:class still renders when the asset is missing', function () {
	$mockRegistry = Mockery::mock(Registry::class);
	$mockRegistry->expects()->tryGetAsset('foo', [])->andReturn(null);

	$latte = new Latte\Engine;
	$latte->addExtension(new LatteExtension($mockRegistry));
	$latte->setLoader(new Latte\Loaders\StringLoader);

	$result = $latte->renderToString('<img n:class="\'foo\'" n:asset?=foo>');
	Assert::same('', $result);
});
