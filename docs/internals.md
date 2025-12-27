# Assets internals

How `nette/assets` works underneath, for agents editing it. Modest: most of it
(the typed asset classes, the mapper interface) is clear from signatures. The
value is in a few real traps — the Registry cache, the `HtmlRenderable`
boundary, the Vite mode switch, and above all the **nonce lifecycle in worker
mode**. One file.

## Mapper → Registry → Asset, and the FIFO cache trap

`Registry` fronts named `Mapper`s and resolves qualified references
(`'mapper:ref'` string or `['mapper', 'ref']` array; a bare reference falls back to
the `'default'` mapper). Its cache has two easy-to-miss properties:

- **It is FIFO, not LRU.** Eviction is `array_shift` (oldest *inserted* entry) once
  `MaxCacheSize` (100) is reached, and a cache **hit does not refresh** the entry's
  position. A hot asset inserted early can still be evicted.
- **Non-scalar options disable caching.** `generateCacheKey` returns `null` if any
  option value is non-scalar (`null` values are allowed), and a `null` key means the
  result is neither read from nor written to the cache — so passing an array/object
  option silently bypasses the cache entirely.

## The `Asset` vs `HtmlRenderable` boundary

`Asset` declares only `__toString()` (with `$url`/`$file` exposed via `@property-read`;
the real property declarations are commented out pending PHP 8.4). **Rendering as an
HTML element requires `HtmlRenderable`** (`getImportElement`/`getPreloadElement`), and
**`GenericAsset` is the one built-in type that does not implement it** — so
`renderAsset`/`renderAssetPreload`/`renderAttributes` all **throw** for a
`GenericAsset`; it can only resolve to a URL.

The `LazyLoad` trait is used by exactly the three types that need **file I/O** for
their properties — `ImageAsset` (dimensions, mime), `AudioAsset` (duration),
`GenericAsset` (mime) — a workaround for lazy initialization before PHP 8.4. Every
other type takes its properties as plain constructor parameters.

## Vite: the mode switch lives upstream; production is restricted

`ViteMapper::getAsset` switches on **whether a `devServer` URL was injected**: if
present it serves dev assets, otherwise it reads `manifest.json` chunks. The higher
gate — *dev server running **and** app in debug mode* — is decided **upstream** in
the DI bridge: outside debug mode `devServer` is always `null`; in debug mode it is
the explicitly configured URL, or auto-detected by `Helpers::detectDevServer` reading
`<basePath>/.vite/nette.json`. The mapper itself only sees the outcome.

In dev mode, a `.js`/`.mjs`/`.ts`(x) reference becomes an `EntryAsset` whose single
import is the **`@vite/client`** script (required for HMR), `.sass`/`.scss` becomes a
`StyleAsset`, and anything else resolves by extension.

**Production can only resolve two things**, and this is the trap agents hit:

1. a chunk present in the **manifest** (and an internal, `_`-prefixed, non-entry
   chunk **throws** "cannot directly access internal chunk"), or
2. a file via the optional **`publicMapper`** (the `assets/public/` directory).

An arbitrary file under `assets/` that Vite never emitted as an entry or bundled is
simply not resolvable in production.

### EntryAsset dependency tree

`createProductionAsset` walks the chunk graph with `collectDependencies`
(**memoized per chunk id**): a chunk's `css` becomes `StyleAsset` **imports**, its
static `imports` become `ScriptAsset` **preloads**, recursively. The result is an
`EntryAsset` (extends `ScriptAsset`) carrying `imports` (rendered as stylesheet
links) and `preloads` (rendered as `modulepreload`). **Dynamic imports are
deliberately not followed** — they are a separate manifest concern and preloading
them would fetch code that may never run.

## Nonce / CSP: cached on the instance, which breaks under a worker

`Runtime::applyNonce` adds a `nonce` attribute to `<script>`/`<link>`/`<style>`
elements, resolving it with **`$this->nonce ??= $this->findNonce()`** and
`findNonce()` scanning **`headers_list()`** for a `'nonce-…'` in the CSP header.

Both halves are process-global, and that is the non-local trap:

- **`$this->nonce` caches on the `Runtime` instance for its whole lifetime**, not
  per request. Under a long-lived worker (RoadRunner / Swoole / FrankenPHP) the
  **first request's nonce (or the `false` "no nonce" result) sticks to every
  later request**, so CSP silently stops matching.
- **`findNonce()` reads `headers_list()`**, global state that is unreliable in a
  worker anyway.

Treat this as a known worker-mode hazard when editing nonce handling. Note that
`Runtime::__construct` already accepts a pre-resolved nonce, but `LatteExtension`
never passes one — the lazy `findNonce()` path is the only one exercised.

## `n:asset` attribute adaptation

`renderAttributes` emits just the attributes for an existing host tag and adapts
the asset's native element to it: an asset whose element is `<img>`/`<script>` is
re-expressed as `<link>` (via `getPreloadElement`) or `<a>` (an `href`) when the
host tag differs, and rejects other mismatches. `completeDimensions` then keeps the
aspect ratio: given one of `width`/`height`, it computes the other from the asset's
known ratio, and if the ratio is unknown it **drops both** rather than emit a
distorted size.

## Navigation map

| Concern | Where |
|---|---|
| Qualified refs, FIFO cache | `Registry::getAsset`/`generateCacheKey` |
| HTML-renderable boundary | `Asset`/`HtmlRenderable`, `Runtime::renderAsset` |
| Lazy file-I/O properties | `LazyLoad` (Image/Audio/Generic) |
| Vite dev/prod switch, restrictions | `ViteMapper::getAsset`/`createProductionAsset` |
| Dev-server gate (debug mode, detection) | `DIExtension::resolveDevServer` |
| Entry dependency tree | `ViteMapper::collectDependencies`, `EntryAsset` |
| Nonce/CSP, worker hazard | `Runtime::applyNonce`/`findNonce` |
| n:asset adaptation | `Runtime::renderAttributes`/`completeDimensions` |
