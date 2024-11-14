<?php

declare(strict_types=1);

namespace Nette\Bridges\Assets;

use Nette;
use Nette\Assets\FilesystemMapper;
use Nette\Assets\Registry;
use Nette\DI\Definitions\Statement;
use Nette\Http\UrlImmutable;
use Nette\Schema\Expect;
use Nette\Utils\FileSystem;


/**
 * Dependency injection extension that integrates asset management into Nette application.
 * Provides configuration of asset mappers and their mapping to URL paths.
 */
final class DIExtension extends Nette\DI\CompilerExtension
{
	public function getConfigSchema(): Nette\Schema\Schema
	{
		return Expect::structure([
			'path' => Expect::string(),
			'url' => Expect::string(),
			'mapping' => Expect::arrayOf(
				Expect::anyOf(
					Expect::string(),
					Expect::structure([
						'path' => Expect::string()->required()->dynamic(),
						'url' => Expect::string()->dynamic(),
						'extension' => Expect::anyOf(Expect::string(), Expect::arrayOf('string')),
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
			? new UrlImmutable(rtrim($this->config->url, '/') . '/')
			: new Statement('@Nette\Http\IRequest::getUrl');
		$basePath = $this->config->path ?? $builder->parameters['wwwDir'];

		$mapping = $this->config->mapping ?? ['default' => 'assets'];

		if (isset($mapping['default'])) {
			$mapping[Registry::DefaultScope] = $mapping['default'];
			unset($mapping['default']);
		}

		$registry = $builder->addDefinition($this->prefix('registry'))
			->setFactory(Registry::class);

		foreach ($mapping as $scope => $item) {
			if (is_string($item)) {
				$url = $this->callOrDefer([$baseUrl, 'resolve'], [$item]);
				$url = $this->callOrDefer([$url, 'getAbsoluteUrl']);
				$path = $this->callOrDefer([FileSystem::class, 'resolvePath'], [$basePath, $item]);
				$mapper = new Statement(FilesystemMapper::class, [$url, $path]);

			} elseif ($item instanceof \stdClass) {
				$url = $this->callOrDefer([$baseUrl, 'resolve'], [$item->url ?? $item->path]);
				$url = $this->callOrDefer([$url, 'getAbsoluteUrl']);
				$path = $this->callOrDefer([FileSystem::class, 'resolvePath'], [$basePath, $item->path]);
				$mapper = new Statement(FilesystemMapper::class, [$url, $path, (array) $item->extension]);

			} else {
				$mapper = $item;
			}

			$registry->addSetup('addMapper', [$scope, $mapper]);
		}
	}


	private function callOrDefer(array $callable, array $args = []): mixed
	{
		foreach (array_merge($callable, $args) as $arg) {
			if ($arg instanceof Statement) {
				return new Statement($callable, $args);
			}
		}

		return $callable(...$args);
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
