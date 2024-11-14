<?php

declare(strict_types=1);

use Nette\Assets\Asset;
use Nette\Assets\Mapper;
use Nette\Assets\Registry;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class MockAsset implements Asset
{
	public function getUrl(): string
	{
		return 'test.jpg';
	}


	public function __toString(): string
	{
		return $this->getUrl();
	}


	public function exists(): bool
	{
		return true;
	}
}

class MockMapper implements Mapper
{
	public function getAsset(string $reference, array $options = []): MockAsset
	{
		return new MockAsset;
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

	for ($i = 0; $i < 12; $i++) { // current cache size is 10
		$assets[$i] = $registry->getAsset("test:asset$i.jpg");
	}

	$first = $registry->getAsset('test:asset0.jpg');
	$last = $registry->getAsset('test:asset11.jpg');

	Assert::notSame(reset($assets), $first); // First asset should be removed from cache
	Assert::same(end($assets), $last); // Last asset should still be in cache
});
