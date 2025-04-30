<?php

declare(strict_types=1);

use Nette\Assets\FilesystemMapper;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


// Create a test file with a known timestamp for consistent testing
touch(__DIR__ . '/fixtures/test.txt', 1_234_567_890);

test('Default versioning behavior with query parameter', function () {
	$mapper = new FilesystemMapper('http://example.com/assets', __DIR__ . '/fixtures');

	$asset = $mapper->getAsset('test.txt');
	Assert::same('http://example.com/assets/test.txt?v=1234567890', $asset->url);
});


test('Disable versioning via options parameter', function () {
	$mapper = new FilesystemMapper('http://example.com/assets', __DIR__ . '/fixtures');

	$asset = $mapper->getAsset('test.txt', ['version' => false]);
	Assert::same('http://example.com/assets/test.txt', $asset->url);
});


test('versioning handles existing query parameters', function () {
	$mapper = new class ('http://example.com/assets', __DIR__ . '/fixtures') extends FilesystemMapper {
		public function resolveUrl(string $reference): string
		{
			// Add a query parameter to the URL
			return parent::resolveUrl($reference) . '?existing=param';
		}
	};

	$asset = $mapper->getAsset('test.txt');
	// Should append version with & instead of ?
	Assert::same('http://example.com/assets/test.txt?existing=param&v=1234567890', $asset->url);
});
