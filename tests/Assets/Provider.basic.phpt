<?php

declare(strict_types=1);

use Nette\Assets\Asset;
use Nette\Assets\AssetNotFoundException;
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
}


class MockMapper implements Mapper
{
	public function __construct(
		private Asset $asset,
	) {
	}


	public function getAsset(string $reference, array $options = []): Asset
	{
		return $this->asset;
	}
}


class ThrowingMockMapper implements Mapper
{
	public function getAsset(string $reference, array $options = []): Asset
	{
		throw new AssetNotFoundException("Asset '$reference' not found");
	}
}


test('Adding and getting mapper', function () {
	$registry = new Registry;
	$mapper = new MockMapper(new MockAsset);

	$registry->addMapper('test', $mapper);
	Assert::same($mapper, $registry->getMapper('test'));
});

test('Adding duplicate mapper throws', function () {
	$registry = new Registry;
	$mapper = new MockMapper(new MockAsset);

	$registry->addMapper('test', $mapper);
	Assert::exception(
		fn() => $registry->addMapper('test', $mapper),
		InvalidArgumentException::class,
		"Asset mapper 'test' is already registered",
	);
});

test('Getting unknown mapper throws', function () {
	$registry = new Registry;
	Assert::exception(
		fn() => $registry->getMapper('unknown'),
		InvalidArgumentException::class,
		"Unknown asset mapper 'unknown'.",
	);
});

test('Getting asset without mapper prefix uses default scope', function () {
	$registry = new Registry;
	$asset = new MockAsset;
	$registry->addMapper('', new MockMapper($asset));

	Assert::same($asset, $registry->getAsset('test.jpg'));
});

test('Getting asset with mapper prefix', function () {
	$registry = new Registry;
	$asset = new MockAsset;
	$registry->addMapper('images', new MockMapper($asset));

	Assert::same($asset, $registry->getAsset('images:test.jpg'));
});

test('Getting asset with array', function () {
	$registry = new Registry;
	$asset = new MockAsset;
	$registry->addMapper('images', new MockMapper($asset));

	Assert::same($asset, $registry->getAsset(['images', 'test.jpg']));
});

test('tryGetAsset returns asset when exists', function () {
	$registry = new Registry;
	$asset = new MockAsset;
	$registry->addMapper('images', new MockMapper($asset));

	Assert::same($asset, $registry->tryGetAsset('images:test.jpg'));
});

test('tryGetAsset returns null when asset does not exist', function () {
	$registry = new Registry;
	$registry->addMapper('missing', new ThrowingMockMapper());

	Assert::null($registry->tryGetAsset('missing:test.jpg'));
});
