<?php

declare(strict_types=1);

use Nette\Assets\Asset;
use Nette\Assets\GenericAsset;
use Nette\Assets\Mapper;
use Nette\Assets\Registry;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class MockMapper implements Mapper
{
	public function getAsset(string $reference, array $options = []): Asset
	{
		return new GenericAsset('test.jpg');
	}
}


test('Asset caching works', function () {
	$registry = new Registry;
	$registry->addMapper('test', new MockMapper);

	$first = $registry->getAsset('test:asset.jpg');
	$second = $registry->getAsset('test:asset.jpg');

	Assert::same($first, $second);
});

test('Cache respects options', function () {
	$registry = new Registry;
	$registry->addMapper('test', new MockMapper);

	$withoutOptions = $registry->getAsset('test:asset.jpg');
	$withOptions = $registry->getAsset('test:asset.jpg', ['version' => 1]);

	Assert::notSame($withoutOptions, $withOptions);
});

test('Cache has limited size', function () {
	$registry = new Registry;
	$registry->addMapper('test', new MockMapper);
	$assets = [];

	for ($i = 0; $i < 102; $i++) { // current cache size is 100
		$assets[$i] = $registry->getAsset("test:asset$i.jpg");
	}

	$first = $registry->getAsset('test:asset0.jpg');
	$last = $registry->getAsset('test:asset101.jpg');

	Assert::notSame(reset($assets), $first); // First asset should be removed from cache
	Assert::same(end($assets), $last); // Last asset should still be in cache
});
