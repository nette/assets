<?php

declare(strict_types=1);

use Nette\Assets\EntryAsset;
use Nette\Assets\ImageAsset;
use Nette\Assets\Registry;
use Nette\Assets\ViteMapper;
use Nette\Bridges\Assets\DIExtension;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$loader = new ContainerLoader(getTempDir(), true);
$key = __FILE__;

$class = $loader->load(function (Compiler $compiler): void {
	$basePath = __DIR__ . '/../Assets/fixtures';
	$compiler->addExtension('assets', new DIExtension);
	$compiler->addConfig([
		'assets' => [
			'baseUrl' => 'https://example.com',
			'basePath' => $basePath,
			'mapping' => [
				'vite-prod' => [
					'type' => 'vite',
					'url' => '/dist',
					'path' => $basePath,
					'manifest' => 'manifest.json',
				],
			],
		],
	]);
}, $key);

$container = new $class;
Assert::type(Container::class, $container);


test('ViteMapper production configuration', function () use ($container): void {
	$registry = $container->getByType(Registry::class);
	Assert::type(Registry::class, $registry);

	$viteMapper = $registry->getMapper('vite-prod');
	Assert::type(ViteMapper::class, $viteMapper);

	$S = DIRECTORY_SEPARATOR;
	$asset = $registry->getAsset('vite-prod:src/main.js');
	Assert::type(EntryAsset::class, $asset);
	Assert::same('https://example.com/dist/assets/main-1a2b3c4d.js', $asset->url);
	assertAssets([
		new Nette\Assets\StyleAsset('https://example.com/dist/assets/main-a1b2c3d4.css', mimeType: 'text/css', file: __DIR__ . "{$S}..{$S}Assets{$S}fixtures/assets/main-a1b2c3d4.css", crossorigin: true),
	], $asset->imports);
	assertAssets([
		new Nette\Assets\ScriptAsset('https://example.com/dist/assets/shared-5e6f7g8h.js', mimeType: 'application/javascript', file: __DIR__ . "{$S}..{$S}Assets{$S}fixtures/assets/shared-5e6f7g8h.js", type: 'module', crossorigin: true),
	], $asset->preloads);

	// fallback to FilesystemMapper
	$asset = $registry->getAsset('vite-prod:image.gif');
	Assert::type(ImageAsset::class, $asset);
});
