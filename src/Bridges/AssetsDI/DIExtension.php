<?php

declare(strict_types=1);

namespace Nette\Bridges\Assets;

use Nette;
use Nette\Assets\FilesystemMapper;
use Nette\Assets\Registry;
use Nette\Bridges\AssetsLatte\LatteExtension;
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
	private ExprProxy $baseUrl;
	private string $basePath;


	public function getConfigSchema(): Nette\Schema\Schema
	{
		return Expect::structure([
			'basePath' => Expect::string(),
			'baseUrl' => Expect::string(),
			'versioning' => Expect::bool(),
			'mapping' => Expect::arrayOf(
				Expect::anyOf(
					Expect::string(),
					Expect::structure([
						'path' => Expect::string('')->dynamic(),
						'url' => Expect::string()->dynamic(),
						'extension' => Expect::anyOf(Expect::string(), Expect::arrayOf('string')),
						'versioning' => Expect::bool(),
					]),
					Expect::type(Statement::class),
				),
			)->default(['default' => 'assets']),
		]);
	}


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$this->baseUrl = $this->config->baseUrl
			? Expr::from(new UrlImmutable(rtrim($this->config->baseUrl, '/') . '/'))
			: Expr::byType('Nette\Http\IRequest')->call('getUrl');

		$this->basePath = $this->config->basePath ?? $builder->parameters['wwwDir'] ?? '(basePath is not defined)';

		$registry = $builder->addDefinition($this->prefix('registry'))
			->setFactory(Registry::class);

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

			$registry->addSetup('addMapper', [$scope, $mapper]);
		}
	}


	private function createFileMapper(\stdClass $config): Statement
	{
		$url = $this->baseUrl->call('resolve', $config->url ?? $config->path)->call('getAbsoluteUrl');
		$path = Expr::call(FileSystem::resolvePath(...), $this->basePath, $config->path);
		return new Statement(FilesystemMapper::class, Expr::resolve([
			'baseUrl' => Expr::call(rtrim(...), $url, '/'),
			'basePath' => Expr::call(rtrim(...), $path, '\/'),
			'extensions' => (array) ($config->extension ?? null),
			'versioning' => $config->versioning ?? $this->config->versioning ?? true,
		]));
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
