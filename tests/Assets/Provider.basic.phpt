<?php

declare(strict_types=1);

use Nette\Assets\Asset;
use Nette\Assets\Provider;
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

class MockProvider implements Provider
{
	public function __construct(
		private Asset $asset,
	) {
	}


	public function getAsset(string $path, array $options = []): Asset
	{
		return $this->asset;
	}
}


test('Adding and getting provider', function () {
	$registry = new Registry;
	$provider = new MockProvider(new MockAsset);

	$registry->addProvider('test', $provider);
	Assert::same($provider, $registry->getProvider('test'));
});

test('Adding duplicate provider throws', function () {
	$registry = new Registry;
	$provider = new MockProvider(new MockAsset);

	$registry->addProvider('test', $provider);
	Assert::exception(
		fn() => $registry->addProvider('test', $provider),
		InvalidArgumentException::class,
		"Asset provider 'test' is already registered",
	);
});

test('Getting unknown provider throws', function () {
	$registry = new Registry;
	Assert::exception(
		fn() => $registry->getProvider('unknown'),
		InvalidArgumentException::class,
		"Unknown asset provider 'unknown'.",
	);
});

test('Getting asset without provider prefix uses default scope', function () {
	$registry = new Registry;
	$asset = new MockAsset;
	$registry->addProvider('', new MockProvider($asset));

	Assert::same($asset, $registry->getAsset('test.jpg'));
});

test('Getting asset with provider prefix', function () {
	$registry = new Registry;
	$asset = new MockAsset;
	$registry->addProvider('images', new MockProvider($asset));

	Assert::same($asset, $registry->getAsset('images:test.jpg'));
});
