<?php declare(strict_types=1);

/**
 * PHPStan type tests.
 */

use Nette\Assets\AudioAsset;
use Nette\Assets\EntryAsset;
use Nette\Assets\FilesystemMapper;
use Nette\Assets\FontAsset;
use Nette\Assets\GenericAsset;
use Nette\Assets\Helpers;
use Nette\Assets\ImageAsset;
use Nette\Assets\Registry;
use Nette\Assets\ScriptAsset;
use Nette\Assets\StyleAsset;
use Nette\Assets\VideoAsset;
use Nette\Assets\ViteMapper;
use Nette\Bridges\AssetsLatte\Runtime;
use function PHPStan\Testing\assertType;


function testParseReference(): void
{
	$result = Helpers::parseReference('images:logo.png');
	assertType('array{string|null, string}', $result);
}


function testGuessMimeTypeFromExtension(): void
{
	$result = Helpers::guessMimeTypeFromExtension('logo.png');
	assertType('string|null', $result);
}


function testCreateAssetFromUrl(): void
{
	$result = Helpers::createAssetFromUrl('/assets/logo.png');
	assertType('Nette\Assets\Asset', $result);
}


/** @param array<string, mixed> $options */
function testRegistryGetAsset(Registry $registry, array $options): void
{
	$result = $registry->getAsset('logo.png', $options);
	assertType('Nette\Assets\Asset', $result);
}


/** @param array<string, mixed> $options */
function testRegistryTryGetAsset(Registry $registry, array $options): void
{
	$result = $registry->tryGetAsset('logo.png', $options);
	assertType('Nette\Assets\Asset|null', $result);
}


/** @param array<string, mixed> $options */
function testRegistryGetAssetWithArray(Registry $registry, array $options): void
{
	$result = $registry->getAsset(['images', 'logo.png'], $options);
	assertType('Nette\Assets\Asset', $result);
}


/** @param array<string, mixed> $options */
function testResolveWithTryFalse(Runtime $runtime, string $asset, array $options): void
{
	$result = $runtime->resolve($asset, $options, false);
	assertType('Nette\Assets\Asset', $result);
}


/** @param array<string, mixed> $options */
function testResolveWithTryTrue(Runtime $runtime, string $asset, array $options): void
{
	$result = $runtime->resolve($asset, $options, true);
	assertType('Nette\Assets\Asset|null', $result);
}


function testImageAssetProperties(ImageAsset $asset): void
{
	assertType('string', $asset->url);
	assertType('string|null', $asset->file);
	assertType('int|null', $asset->width);
	assertType('int|null', $asset->height);
	assertType('string|null', $asset->mimeType);
	assertType('string|null', $asset->alternative);
	assertType('bool', $asset->lazyLoad);
	assertType('int', $asset->density);
	assertType('bool|string|null', $asset->crossorigin);
}


function testScriptAssetProperties(ScriptAsset $asset): void
{
	assertType('string', $asset->url);
	assertType('string|null', $asset->file);
	assertType('string|null', $asset->type);
	assertType('string|null', $asset->integrity);
	assertType('bool|string|null', $asset->crossorigin);
}


function testStyleAssetProperties(StyleAsset $asset): void
{
	assertType('string', $asset->url);
	assertType('string|null', $asset->file);
	assertType('string|null', $asset->media);
	assertType('string|null', $asset->integrity);
	assertType('bool|string|null', $asset->crossorigin);
}


function testAudioAssetProperties(AudioAsset $asset): void
{
	assertType('string', $asset->url);
	assertType('string|null', $asset->file);
	assertType('float|null', $asset->duration);
	assertType('string|null', $asset->mimeType);
}


function testVideoAssetProperties(VideoAsset $asset): void
{
	assertType('string', $asset->url);
	assertType('string|null', $asset->file);
	assertType('int|null', $asset->width);
	assertType('int|null', $asset->height);
	assertType('string|null', $asset->mimeType);
	assertType('float|null', $asset->duration);
	assertType('string|null', $asset->poster);
	assertType('bool', $asset->autoPlay);
}


function testFontAssetProperties(FontAsset $asset): void
{
	assertType('string', $asset->url);
	assertType('string|null', $asset->file);
	assertType('string|null', $asset->mimeType);
	assertType('string|null', $asset->integrity);
	assertType('bool|string|null', $asset->crossorigin);
}


function testGenericAssetProperties(GenericAsset $asset): void
{
	assertType('string', $asset->url);
	assertType('string|null', $asset->file);
	assertType('string|null', $asset->mimeType);
	assertType('string|null', $asset->media);
	assertType('string|null', $asset->integrity);
}


function testEntryAssetProperties(EntryAsset $asset): void
{
	assertType('string', $asset->url);
	assertType('string|null', $asset->file);
	assertType('list<Nette\Assets\HtmlRenderable>', $asset->imports);
	assertType('list<Nette\Assets\HtmlRenderable>', $asset->preloads);
	assertType('string|null', $asset->type);
	assertType('string|null', $asset->integrity);
	assertType('bool|string|null', $asset->crossorigin);
}


function testFilesystemMapperGetAsset(FilesystemMapper $mapper): void
{
	$result = $mapper->getAsset('logo.png');
	assertType('Nette\Assets\Asset', $result);
}


function testViteMapperGetAsset(ViteMapper $mapper): void
{
	$result = $mapper->getAsset('app.js');
	assertType('Nette\Assets\Asset', $result);
}
