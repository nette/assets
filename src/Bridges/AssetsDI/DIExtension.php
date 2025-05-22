<?php

declare(strict_types=1);

namespace Nette\Bridges\Assets;

use Nette;
use Nette\Assets\FilesystemMapper;
use Nette\Assets\Registry;
use Nette\Bridges\AssetsLatte\LatteExtension;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;


/**
 * Dependency injection extension that integrates asset management into Nette application.
 * Provides configuration of asset mappers and their mapping to URL paths.
 */
final class DIExtension extends Nette\DI\CompilerExtension
{
	private ?string $basePath;
	private int $needVariable;


	public function getConfigSchema(): Nette\Schema\Schema
	{
		return Expect::structure([
			'basePath' => Expect::string()->dynamic(),
			'baseUrl' => Expect::string()->dynamic(),
			'mapping' => Expect::arrayOf(
				Expect::anyOf(
					Expect::string(),
					Expect::structure([
						'path' => Expect::string()->dynamic(),
						'url' => Expect::string()->dynamic(),
						'extension' => Expect::anyOf(Expect::string(), Expect::arrayOf('string')),
					]),
					Expect::type(Statement::class),
				),
			)->default(['default' => 'assets']),
		]);
	}


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$registry = $builder->addDefinition($this->prefix('registry'))
			->setFactory(Registry::class);

		$this->needVariable = 0;
		$this->basePath = $this->config->basePath ?? $builder->parameters['wwwDir'] ?? null;

		foreach ($this->config->mapping as $scope => $item) {
			if (is_string($item)) {
				$mapper = str_contains($item, '\\')
					? new Statement($item)
					: $this->createFileMapper((object) ['path' => $item]);

			} elseif ($item instanceof \stdClass) {
				$mapper = $this->createFileMapper($item);
			} else {
				$mapper = $item;
			}

			if ($this->needVariable === 1) {
				$baseUrl = $this->config->baseUrl ?? new Statement([new Statement('@Nette\Http\IRequest::getUrl'), 'getBaseUrl']);
				$registry->addSetup('$baseUrl = new Nette\Http\UrlImmutable(?)', [new Statement("rtrim(?, '/') . '/'", [$baseUrl])]);
			}

			$registry->addSetup('addMapper', [$scope, $mapper]);
		}
	}


	private function createFileMapper(\stdClass $config): Statement
	{
		$this->needVariable++;
		return new Statement(FilesystemMapper::class, [
			'baseUrl' => $this->resolveUrl($config),
			'basePath' => $this->resolvePath($config),
			'extensions' => (array) ($config->extension ?? null),
		]);
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


	private function resolvePath(\stdClass $config): string|Statement
	{
		$path = isset($this->basePath, $config->path)
			? new Statement('Nette\Utils\FileSystem::resolvePath(?, ?)', [$this->basePath, $config->path])
			: $config->path ?? $this->basePath ?? throw new \LogicException("Assets: 'basePath' is not defined");
		return new Statement("rtrim(?, '\\/')", [$path]);
	}


	private function resolveUrl(\stdClass $config): Statement
	{
		$url = new Statement('$baseUrl->resolve(?)->getAbsoluteUrl()', [$config->url ?? $config->path ?? '']);
		return new Statement("rtrim(?, '/')", [$url]);
	}
}
