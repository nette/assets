<?php

declare(strict_types=1);

namespace Nette\Bridges\Assets;

use Nette;
use Nette\Assets\FilesystemProvider;
use Nette\Assets\Registry;
use Nette\DI\Definitions\Statement;
use Nette\Http\UrlImmutable;
use Nette\Schema\Expect;


/**
 * Dependency injection extension that integrates asset management into Nette application.
 * Provides configuration of asset providers and their mapping to URL paths.
 */
final class DIExtension extends Nette\DI\CompilerExtension
{
	public function getConfigSchema(): Nette\Schema\Schema
	{
		return Expect::structure([
			'url' => Expect::string(),
			'source' => Expect::string(),
			'mapping' => Expect::arrayOf(
				Expect::anyOf(
					Expect::string(),
					Expect::structure([
						'url' => Expect::string()->required()->dynamic(),
						'source' => Expect::string()->required()->dynamic(),
						'extensions' => Expect::arrayOf('string'),
					]),
					Expect::type(Statement::class),
				),
			),
		]);
	}


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$baseUrl = $this->config->url
			? new Statement(UrlImmutable::class, [rtrim($this->config->url, '/') . '/'])
			: new Statement('@Nette\Http\Request::getUrl');
		$sourceDir = $this->config->source ?? $builder->parameters['wwwDir'];

		$mapping = $this->config->mapping ?? ['default' => 'assets'];
		$mapping[Registry::DefaultScope] = $mapping['default'] ?? null;
		unset($mapping['default']);

		$registry = $builder->addDefinition($this->prefix('registry'))
			->setFactory(Registry::class);

		foreach ($mapping as $scope => $item) {
			if (is_string($item)) {
				$url = new Statement([$baseUrl, 'resolve'], [$item]);
				$url = new Statement([$url, 'getAbsoluteUrl']);
				$source = new Statement(['', 'join'], [[$sourceDir, '/' . ltrim($item, '/')]]);
				$item = new Statement(FilesystemProvider::class, [$url, $source]);
			} elseif ($item instanceof \stdClass) {
				$url = new Statement([$baseUrl, 'resolve'], [$item->url]);
				$url = new Statement([$url, 'getAbsoluteUrl']);
				$item = new Statement(FilesystemProvider::class, [$url, $item->source, $item->extensions]);
			}
			$registry->addSetup('addProvider', [$scope, $item]);
		}
	}


	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		if ($name = $builder->getByType(Nette\Bridges\ApplicationLatte\LatteFactory::class)) {
			$builder->getDefinition($name)
				->getResultDefinition()
				->addSetup('addExtension', [new Statement(LatteExtension::class)]);
		}
	}
}
