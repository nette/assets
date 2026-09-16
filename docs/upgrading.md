# Upgrading nette/assets

## 1.0.4

- **Changed** `Nette\Assets\ViteMapper::getAsset()` now returns `Nette\Assets\EntryAsset` for `.jsx` and `.tsx` references and `Nette\Assets\StyleAsset` for `.sass` and `.scss` references when the dev server is active; previously only `.js`, `.mjs` and `.ts` became an `Nette\Assets\EntryAsset` and everything else fell back to a class guessed from the MIME type (5ca891a1)

## 1.0.2

- **Removed** `Nette\Assets\EntryAsset::$mimeType` together with the `$mimeType` constructor parameter; the type is implied by the asset class, and every following positional constructor argument shifts by one position (e907489c)
- **Removed** `Nette\Assets\ScriptAsset::$mimeType` together with the `$mimeType` constructor parameter; the type is implied by the asset class, and every following positional constructor argument shifts by one position (e907489c)
- **Removed** `Nette\Assets\StyleAsset::$mimeType` together with the `$mimeType` constructor parameter; the type is implied by the asset class, and every following positional constructor argument shifts by one position (e907489c)
- **Signature** `$mimeType` of the constructors of `Nette\Assets\AudioAsset`, `Nette\Assets\FontAsset`, `Nette\Assets\ImageAsset` and `Nette\Assets\VideoAsset` moved from the second position: behind `$file` in the audio and font asset, behind `$height` in the image and video asset; a positional call passes its values to other parameters without an error, so pass them by name (df217ac0, 2d3484a0)
- **Changed** `Nette\Assets\AudioAsset::$mimeType` is now guessed from the extension of `$file`, or of `$url` when no file is given; previously it stayed `null` unless passed to the constructor (df217ac0)
- **Changed** `Nette\Assets\FontAsset::$mimeType` is now guessed from the extension of `$file`, or of `$url` when no file is given; previously it stayed `null` unless passed to the constructor (df217ac0)
- **Changed** `Nette\Assets\ImageAsset::$mimeType` is now read from the image file with `getimagesize()` on first access; previously it stayed `null` unless passed to the constructor (2d3484a0)

## 1.0.1

- **Config** `assets.mapping.<name>.devServer` now defaults to `true`, which in debug mode detects a running Vite dev server from the `.vite/nette.json` file even when the key is not present in the configuration at all; previously the default was `false`; the key also no longer accepts a dynamic value and must be a literal; set `devServer: false` to keep the dev server disabled (da38b99f)
