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
	private Statement|UrlImmutable $baseUrl;
	private string $basePath;


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

		$this->baseUrl = $this->config->url
			? new UrlImmutable(rtrim($this->config->url, '/') . '/')
			: new Statement('@Nette\Http\IRequest::getUrl');
		$this->basePath = $this->config->path ?? $builder->parameters['wwwDir'];

		$mapping = $this->config->mapping ?? ['default' => 'assets'];

		if (isset($mapping['default'])) {
			$mapping[Registry::DefaultScope] = $mapping['default'];
			unset($mapping['default']);
		}

		$registry = $builder->addDefinition($this->prefix('registry'))
			->setFactory(Registry::class);

		foreach ($mapping as $scope => $item) {
			if (is_string($item)) {
				$mapper = $this->createFileMapper((object) ['path' => $item]);
			} elseif ($item instanceof \stdClass) {
				$mapper = $this->createFileMapper($item);
			} else {
				$mapper = $item;
			}

			$registry->addSetup('addMapper', [$scope, $mapper]);
		}
	}


	private function createFileMapper(\stdClass $config): Statement
	{
		$url = $this->callOrDefer([$this->baseUrl, 'resolve'], [$config->url ?? $config->path]);
		return new Statement(FilesystemMapper::class, [
			'baseUrl' => $this->callOrDefer([$url, 'getAbsoluteUrl']),
			'basePath' => $this->callOrDefer([FileSystem::class, 'resolvePath'], [$this->basePath, $config->path]),
			'extensions' => (array) ($config->extension ?? null),
		]);
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
