<?php

declare(strict_types=1);

use Nette\Assets\EntryAsset;
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
		'parameters' => [
			'wwwDir' => $basePath,
			'debugMode' => false,
		],
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

	$asset = $registry->getAsset('vite-prod:src/main.js');
	Assert::type(EntryAsset::class, $asset);
	Assert::same('https://example.com/dist/assets/main-1a2b3c4d.js', $asset->url);

	Assert::count(2, $asset->dependencies);
});
