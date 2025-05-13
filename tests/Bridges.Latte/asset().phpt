<?php

declare(strict_types=1);

use Nette\Assets\GenericAsset;
use Nette\Assets\Registry;
use Nette\Bridges\AssetsLatte\LatteExtension;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('asset() function calls Registry::getAsset()', function () {
	$asset = new GenericAsset('asset-url');
	$mockRegistry = Mockery::mock(Registry::class);
	$mockRegistry->expects()
		->getAsset('foo', [123, 'foo' => 'bar'])
		->andReturn($asset);

	$latte = new Latte\Engine;
	$latte->addExtension(new LatteExtension($mockRegistry));
	$latte->setLoader(new Latte\Loaders\StringLoader);

	$result = $latte->renderToString('{=asset(foo, 123, foo: bar)}');
	Assert::same('asset-url', $result);

	$result = $latte->renderToString('{=asset($asset, 123, foo: bar)}', ['asset' => $asset]);
	Assert::same('asset-url', $result);
});


test('tryAsset() function calls Registry::tryGetAsset()', function () {
	$mockRegistry = Mockery::mock(Registry::class);
	$mockRegistry->expects()
		->tryGetAsset('foo', [123, 'foo' => 'bar'])
		->andReturn(new GenericAsset('asset-url'));

	$latte = new Latte\Engine;
	$latte->addExtension(new LatteExtension($mockRegistry));
	$latte->setLoader(new Latte\Loaders\StringLoader);

	$result = $latte->renderToString('{=tryAsset(foo, 123, foo: bar)}');
	Assert::same('asset-url', $result);
});

test('tryAsset() function returns empty string when asset not found', function () {
	$mockRegistry = Mockery::mock(Registry::class);
	$mockRegistry->expects()
		->tryGetAsset('nonexistent', [])
		->andReturn(null);

	$latte = new Latte\Engine;
	$latte->addExtension(new LatteExtension($mockRegistry));
	$latte->setLoader(new Latte\Loaders\StringLoader);

	$result = $latte->renderToString('{=tryAsset(nonexistent)}');
	Assert::same('', $result);
});
