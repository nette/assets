# To My Agents!

It is my fervent wish that this file guide every AI coding agent working with code in this repository.

## Documentation

Any distilled, agent-facing documentation for this package - how it works
internally and the rationale behind key design decisions - lives in `docs/`.
Consult it before non-trivial changes; it is the source of truth from which the
public manual is distilled.

The real traps - the Registry cache, the `HtmlRenderable` boundary, the Vite
dev/prod switch, and the CSP-nonce worker hazard - are in `docs/internals.md`.
Read it before touching those areas.

## Project Overview

**Nette Assets** is a PHP library for elegant asset management with automatic
versioning, lazy loading, and multiple storage backends (filesystem, Vite),
integrating with Latte templates and the Nette framework but usable standalone.

- **PHP Version**: 8.1+
- **Package**: `nette/assets`

## Essential Commands

```bash
# Run all tests
vendor/bin/tester tests -s

# Run a single test file
php tests/Assets/ImageAsset.phpt

# Static analysis
composer phpstan
```

## Conventions

- Every file starts with `declare(strict_types=1);`; Nette Coding Standard.
- Tests are Nette Tester `.phpt` files under `tests/Assets/`; use `test()` /
  `testException()` and `Assert::*`. **Don't put a comment before `test()`** - the
  description argument already documents the case. Fixtures live in
  `tests/Assets/fixtures/`; `tests/bootstrap.php` provides `getTempDir()`,
  `createContainer()`, and `assertAssets()`.
- Paths are handled with forward slashes internally; the library normalizes across
  platforms, so keep new code Windows-safe.

## Working in this repo

- **The Registry cache is FIFO, not LRU**, and non-scalar options bypass it
  entirely - see `docs/internals.md` before changing caching.
- **Only `HtmlRenderable` assets render as HTML.** `GenericAsset` is the one
  built-in type that doesn't implement it, so rendering it throws; it can only
  resolve to a URL. New asset types must decide this deliberately.
- **The CSP nonce caches on the `Runtime` instance**, which leaks across requests
  under a long-lived worker (RoadRunner/Swoole/FrankenPHP). Treat nonce handling as
  a known worker-mode hazard.
- **Vite production resolves only manifest entries and `assets/public/` files** -
  arbitrary files under `assets/` are unresolvable. The dev/prod decision is made
  upstream in the DI bridge, not in `ViteMapper`.
- User-facing how-to (NEON `assets:` config, Vite setup, `{asset}`/`n:asset` Latte
  syntax) is manual material and lives in the public web docs, not here.
