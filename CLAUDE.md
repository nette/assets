# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Nette Assets** is a PHP library for elegant asset management with automatic versioning, lazy loading, and support for multiple storage backends (filesystem, Vite). It provides seamless integration with Latte templates and the Nette framework.

**Key characteristics:**
- PHP 8.1+ requirement
- Zero-configuration default setup
- Registry pattern for managing multiple asset mappers
- Type-specific asset classes (Image, Script, Style, Audio, Video, Font, Generic)
- Lazy property loading for performance
- Vite integration with dev server auto-detection

## Essential Commands

### Testing

```bash
# Run all tests
vendor/bin/tester tests -s -C

# Run specific test file
php tests/Assets/ImageAsset.phpt

# Run tests in specific directory
vendor/bin/tester tests/Assets/ -s -C
```

### Static Analysis

```bash
# Run PHPStan
composer run phpstan

# Or directly
vendor/bin/phpstan analyse
```

### Code Standards

```bash
# Coding style is checked via GitHub Actions
# See .github/workflows/coding-style.yml
```

## Architecture

### Core Pattern: Mapper → Registry → Asset

The library uses a three-layer architecture:

1. **Mapper** - Resolves asset references to Asset objects
   - `FilesystemMapper` - Files from local filesystem with versioning
   - `ViteMapper` - Vite-generated assets with manifest.json support
   - Custom mappers can implement the simple `Mapper` interface

2. **Registry** - Central point managing multiple named mappers
   - Handles qualified references like `'images:logo.png'` or `['images', 'logo.png']`
   - Built-in LRU cache (max 100 entries) for resolved assets
   - Falls back to 'default' mapper when no prefix specified

3. **Asset** - Type-specific classes representing files
   - Base interface: `Asset` with `$url`, `$file`, `__toString()`
   - Specialized types: ImageAsset, ScriptAsset, StyleAsset, AudioAsset, VideoAsset, FontAsset, GenericAsset
   - EntryAsset for Vite entry points with dependencies
   - Properties are lazy-loaded using `LazyLoad` trait

### Asset Type Properties

Different asset types provide different readonly properties (all lazy-loaded):

- **ImageAsset**: `width`, `height`, `mimeType`, `alt`, `loading`
- **ScriptAsset**: `type`, `integrity`, `crossorigin`
- **StyleAsset**: `media`, `integrity`, `crossorigin`
- **AudioAsset**: `duration` (estimated for MP3 via `Helpers::guessMP3Duration()`), `mimeType`
- **VideoAsset**: `width`, `height`, `duration`, `poster`, `autoplay`, `mimeType`
- **FontAsset**: `mimeType`, `crossorigin` (always true for fonts)
- **GenericAsset**: `mimeType`
- **EntryAsset**: `imports` (array of StyleAsset), `preloads` (array of ScriptAsset), `crossorigin`

### Key Design Patterns

**Lazy Property Loading:**
- The `LazyLoad` trait defers expensive operations (reading dimensions, MIME types) until accessed
- Used across all asset types to keep asset creation fast
- Workaround for PHP < 8.4 lazy initialization

**Asset Type Detection:**
- `Helpers::createAssetFromUrl()` is the factory method
- Automatically selects asset class based on MIME type (from extension or explicit parameter)
- Extension-to-MIME mapping in `Helpers::ExtensionToMime`

**Mapper Options:**
- Every `Mapper::getAsset()` accepts `array $options` parameter
- `FilesystemMapper` supports `'version' => bool` option
- Custom mappers can define their own options for flexibility

**Vite Integration:**
- Automatic dev server detection via `.vite/vite-dev.json`
- Development mode: serves from Vite dev server with HMR
- Production mode: reads `manifest.json` for hashed filenames
- `EntryAsset` handles CSS imports and script preloads automatically

### Directory Structure

```
src/
├── Assets/               # Core library classes
│   ├── Asset.php        # Base interface
│   ├── *Asset.php       # Type-specific implementations
│   ├── Mapper.php       # Mapper interface
│   ├── FilesystemMapper.php
│   ├── ViteMapper.php
│   ├── Registry.php     # Central asset manager
│   ├── Helpers.php      # Static utilities
│   ├── LazyLoad.php     # Lazy loading trait
│   └── exceptions.php   # AssetNotFoundException
└── Bridges/
    ├── AssetsDI/        # Nette DI integration
    └── AssetsLatte/     # Latte template integration
```

### Bridge Architecture

**DI Extension (`Bridges/AssetsDI/DIExtension.php`):**
- Integrates with Nette DI container
- Parses NEON configuration for `assets:` section
- Creates Registry and mapper services
- Handles basePath/baseUrl resolution
- Supports dynamic parameters via Nette\Schema\DynamicParameter

**Latte Extension (`Bridges/AssetsLatte/LatteExtension.php`):**
- Provides `{asset}` tag and `n:asset` attribute
- Implements `asset()` and `tryAsset()` functions
- Implements `{preload}` tag for resource hints
- Node classes in `AssetsLatte/Nodes/` for AST compilation

## Testing Conventions

### Test File Structure

All tests use `.phpt` extension with Nette Tester's functional style:

```php
<?php
declare(strict_types=1);

use Tester\Assert;
require __DIR__ . '/../bootstrap.php';

test('Description of what is being tested', function () {
    $asset = new ImageAsset('', file: __DIR__ . '/fixtures/image.gif');
    Assert::same(176, $asset->width);
});
```

**Key conventions:**
- Use `test()` function with clear description
- DO NOT add comments before `test()` - description parameter serves this purpose
- Use `testException()` for tests that end with exception
- Group related tests in same file

### Fixtures

Test fixtures are stored in `tests/Assets/fixtures/` (images, audio, video files for property detection tests).

### Helper Functions

`tests/bootstrap.php` provides:
- `getTempDir()` - Per-process temporary directory with garbage collection
- `createContainer($config)` - Creates DI container with assets extension
- `assertAssets($expected, $actual)` - Compares arrays of Asset objects

## Development Workflow

### Adding New Asset Types

1. Create class extending appropriate base or implementing `Asset`
2. Add MIME type mapping to `Helpers::ExtensionToMime`
3. Update `Helpers::createAssetFromUrl()` match expression
4. Add tests in `tests/Assets/NewAsset.phpt`
5. Update README.md with new asset type capabilities

### Adding New Mapper

1. Implement `Mapper` interface with `getAsset(string $reference, array $options): Asset`
2. Throw `AssetNotFoundException` with descriptive message when asset not found
3. Use `Helpers::createAssetFromUrl()` to create appropriate Asset type
4. Document supported options in class docblock
5. Add tests covering: basic retrieval, missing assets, option handling

### Versioning Strategy

**FilesystemMapper:**
- Uses file modification time (`filemtime()`) as version parameter
- Appends `?v={timestamp}` or `&v={timestamp}` to URLs
- Configurable via constructor `$versioning` and option `['version' => bool]`

**ViteMapper:**
- Vite handles versioning by including hash in filename
- No query parameter versioning needed for entry points

## Important Implementation Details

### Windows Path Handling

Always use forward slashes in paths internally. The library normalizes paths across platforms.

### Error Messages

`AssetNotFoundException` includes:
- Clear description of what wasn't found
- Full path or reference that failed
- Method `qualifyReference($mapper, $reference)` adds context when thrown from Registry

### Performance Considerations

- Registry cache is intentionally limited to 100 entries (LRU)
- Cache key includes serialized options hash for complex options
- Non-scalar options disable caching (return null from `generateCacheKey()`)
- Lazy loading prevents unnecessary file I/O

### Nette Framework Integration

The library is framework-agnostic at core but provides Nette bridges:
- DI Extension handles configuration in `config.neon`
- Latte Extension adds template helpers
- Works standalone without Nette (manual Registry setup)

## Common Patterns

### Custom Mapper Template

```php
class MyMapper implements Mapper
{
    public function __construct(
        private string $baseUrl,
        private MyStorage $storage,
    ) {}

    public function getAsset(string $reference, array $options = []): Asset
    {
        Helpers::checkOptions($options, ['quality']); // Validate options

        if (!$this->storage->exists($reference)) {
            throw new AssetNotFoundException("Asset '$reference' not found");
        }

        $url = $this->baseUrl . '/' . $reference;
        $localPath = $this->storage->getPath($reference);

        return Helpers::createAssetFromUrl($url, $localPath);
    }
}
```

### Working with Entry Assets (Vite)

EntryAsset bundles multiple dependencies:
- `$imports` - CSS files to include (rendered as `<link rel="stylesheet">`)
- `$preloads` - JS files to preload (rendered as `<link rel="modulepreload">`)
- Main script rendered as `<script type="module">`

Template rendering automatically handles all dependencies when using `{asset}` tag.

## Configuration

### NEON Configuration Structure

```neon
assets:
    basePath: ...      # (string) base directory for relative mapper paths, defaults to %wwwDir%
    baseUrl: ...       # (string) base URL for relative mapper URLs, defaults to %baseUrl%
    versioning: ...    # (bool) global versioning setting, defaults to true
    mapping: ...       # (array) mapper definitions, defaults to ['default' => 'assets']
```

### Path and URL Resolution Rules

**Path Resolution:**
- Relative paths are resolved from `basePath` (defaults to `%wwwDir%`)
- Absolute paths are used as-is
- Example: `path: img` → `%wwwDir%/img`
- Example: `path: /var/shared/uploads` → `/var/shared/uploads`

**URL Resolution:**
- Relative URLs are resolved from `baseUrl` (defaults to `%baseUrl%`)
- Absolute URLs (with scheme or starting with `//`) are used as-is
- If `url` is not specified, it uses the value of `path`
- Example: `url: images` → `%baseUrl%/images`
- Example: `url: https://cdn.example.com` → `https://cdn.example.com`

### Mapper Configuration Formats

**Simple string notation** (creates FilesystemMapper):
```neon
assets:
    mapping:
        default: assets     # Files in %wwwDir%/assets, URLs like /assets/...
        images: img         # Files in %wwwDir%/img, URLs like /img/...
```

**Detailed array notation** (FilesystemMapper with options):
```neon
assets:
    mapping:
        images:
            path: img                        # directory to search
            url: images                      # URL prefix
            versioning: true                 # enable versioning
            extension: [webp, jpg, png]      # try extensions in order
```

**ViteMapper configuration:**
```neon
assets:
    mapping:
        default:
            type: vite                       # use ViteMapper
            path: assets                     # build output directory
            manifest: assets/.vite/manifest.json  # manifest location
            devServer: true                  # auto-detect dev server (default)
            versioning: true                 # for public dir files only
            extension: [webp, jpg]           # for public dir files only
```

**Service reference** (custom mapper):
```neon
services:
    s3mapper: App\Assets\S3Mapper(%s3.bucket%)

assets:
    mapping:
        cloud: @s3mapper
        database: App\Assets\DatabaseMapper(@database.connection)
```

### Extension Auto-Detection

FilesystemMapper and ViteMapper (for public dir) support automatic extension detection:

```neon
assets:
    mapping:
        images:
            path: img
            extension: [webp, jpg, png]  # Try webp first, then jpg, then png
```

When requesting `{asset 'images:logo'}`, it searches for:
1. `logo.webp`
2. `logo.jpg`
3. `logo.png`

Returns first match found. Perfect for progressive enhancement (WebP with fallback).

**Making extension optional:**
```neon
extension: [js, '']  # Try with .js first, then without extension
```

## Vite Integration Details

### Entry Points and Dependency Tree

An **entry point** is your main JavaScript/TypeScript file that imports other modules. Vite follows these imports to build a dependency tree:

```js
// assets/app.js - entry point
import './style.css'           // CSS dependency
import naja from 'naja'        // NPM package
import './components/menu.js'  // Local module
```

**Multiple entry points** for different sections:
```js
// vite.config.ts
export default defineConfig({
    plugins: [
        nette({
            entry: [
                'app.js',      // public pages
                'admin.js',    // admin panel
            ],
        }),
    ],
});
```

### Critical Loading Restrictions

**On production, you can ONLY load:**
1. **Entry points** defined in Vite config `entry`
2. **Files from `assets/public/` directory** (copied as-is, not processed)

**You CANNOT load** arbitrary files from `assets/` directory:
```latte
{* ✓ Works - it's an entry point *}
{asset 'app.js'}

{* ✓ Works - it's in assets/public/ *}
{asset 'favicon.ico'}

{* ✗ FAILS - random file in assets/ not referenced anywhere *}
{asset 'components/button.js'}
```

Files are included in the build only if:
- They are entry points, OR
- They are imported (directly or indirectly) by JavaScript/CSS, OR
- They are in the `public/` directory

### Development vs Production Behavior

**ViteMapper automatically switches modes based on:**
1. Vite dev server is running (auto-detected via `.vite/vite-dev.json`)
2. Application is in debug mode (`%debugMode%`)

**Development mode** (both conditions true):
```latte
{asset 'app.js'}
{* Renders: <script src="http://localhost:5173/@vite/client" type="module"></script>
            <script src="http://localhost:5173/app.js" type="module"></script> *}
```

**Production mode**:
```latte
{asset 'app.js'}
{* Renders: <script src="/assets/app-4a8f9c7.js" type="module"></script>
            <link rel="stylesheet" href="/assets/app-a1b2c3d4.css"> *}
```

### Dev Server Configuration

**Default behavior** (`devServer: true`):
- Auto-detects dev server on current host
- Only activates if app in debug mode AND dev server running
- No configuration needed

**Custom dev server URL:**
```neon
assets:
    mapping:
        default:
            type: vite
            devServer: https://localhost:5173  # explicit URL
```

**Disable dev server integration:**
```neon
assets:
    mapping:
        default:
            type: vite
            devServer: false  # always use built files
```

### CORS for Cross-Domain Development

When PHP app runs on different domain than Vite dev server (e.g., `myapp.local` vs `localhost:5173`):

**Option 1: Configure CORS in Vite**
```js
// vite.config.ts
export default defineConfig({
    server: {
        cors: {
            origin: 'http://myapp.local',  // your PHP app URL
        },
    },
});
```

**Option 2: Run Vite on same domain**
```js
// vite.config.ts
export default defineConfig({
    server: {
        host: 'myapp.local',  // same as PHP app
    },
});
```

### Public Directory

Files in `assets/public/` are **copied as-is** without processing:

```
assets/
├── public/           # Copied without processing
│   ├── favicon.ico
│   ├── robots.txt
│   └── images/
│       └── og-image.jpg
├── app.js           # Processed by Vite
└── style.css        # Processed by Vite
```

Use FilesystemMapper features for public files:
```neon
assets:
    mapping:
        default:
            type: vite
            extension: [webp, jpg, png]  # Extension detection for public files
            versioning: true             # Versioning for public files
```

Configure public directory in Vite:
```js
export default defineConfig({
    publicDir: 'public',  // default: 'public' (relative to 'root')
});
```

### Dynamic Imports and Code Splitting

Dynamic imports create separate chunks loaded on-demand:

```js
// Main bundle
button.addEventListener('click', async () => {
    let { Chart } = await import('./components/chart.js')  // Separate chunk
    new Chart(data)
})
```

**`{asset}` does NOT preload dynamic chunks** - intentional to avoid downloading unused code.

To preload critical dynamic imports:
```latte
{asset 'app.js'}
{preload 'components/chart.js'}  {* Preload critical dynamic import *}
```

## Latte Template Integration

### Optional Asset Handling

**`{asset?}` tag** - renders nothing if asset missing:
```latte
{asset? 'optional-banner.jpg'}  {* No error if missing *}
```

**`n:asset?` attribute** - skips attribute if asset missing:
```latte
<img n:asset?="user-avatar.jpg" alt="Avatar" class="avatar">
```

**`tryAsset()` function** - returns null if asset missing:
```latte
{var $avatar = tryAsset('user-avatar.jpg') ?? asset('default-avatar.jpg')}
<img n:asset=$avatar alt="Avatar">
```

### Asset Tag Behavior in Different Contexts

**Outside HTML attributes** - renders complete HTML element:
```latte
{asset 'hero.jpg'}
{* Renders: <img src="/assets/hero.jpg?v=123" width="1920" height="1080"> *}

{asset 'app.js'}
{* Renders: <script src="/assets/app.js?v=456" type="module"></script> *}
```

**Inside HTML attributes** - outputs just URL:
```latte
<div style="background-image: url({asset 'bg.jpg'})">
<img srcset="{asset 'logo@2x.png'} 2x">
```

### Using Variables with n:asset

```latte
{* Simple variable *}
<img n:asset="$product->image">

{* With mapper prefix - use curly brackets *}
<img n:asset="images:{$product->image}">

{* Array notation *}
<img n:asset="[images, $product->image]">

{* With options *}
<link n:asset="print.css, version: false" media="print">
```
