# gemvc/helper

Helper utilities for the [GEMVC](https://gemvc.de) framework.

Composer package: `gemvc/helper`  
GitHub repo: [gemvc/helper](https://github.com/gemvc/helper)

> **WARNING**
>
> This is an **internal GEMVC ecosystem package**. It is designed to be installed **with** `gemvc/library`, not on its own.
>
> **Do not** run `composer require gemvc/helper` in a generic PHP project expecting a standalone utility library — several classes depend on `gemvc/library` (HTTP layer, system page paths, etc.) and **may not work** without the full framework in `vendor/`.
>
> Supported install path: `composer require gemvc/library` (which pulls in `gemvc/helper` automatically).

## Installation

Installed automatically with the framework:

```bash
composer require gemvc/library
```

`gemvc/library` requires `gemvc/helper` — you do not need a separate install in normal apps.

## Classes

| Class | Purpose |
|-------|---------|
| `ProjectHelper` | Project root, `.env`, URLs, library paths |
| `CryptHelper` | Password hashing (Argon2i) |
| `TypeChecker` | HTTP input validation — see [supported types](#version-110--schema-validation-types) below |
| `TypeHelper` | Type utilities (GUID, timestamps, reflection helpers) |
| `FileHelper` | AES-256-CBC file encryption |
| `ImageHelper` | WebP conversion |
| `JsonHelper`, `StringHelper`, `WebHelper` | General utilities |
| `ServerMonitorHelper`, `NetworkHelper` | Monitoring metrics |

Namespace: `Gemvc\Helper\` (unchanged from monorepo layout).

## Pairing with gemvc/library (no circular Composer dependency)

- **`gemvc/library` requires `gemvc/helper`** — normal app install path
- **`gemvc/helper` does NOT require `gemvc/library`** — avoids `library → helper → library` cycle

Some classes (`ChatGptClient`, legacy `TraceKitToolkit`) use `Gemvc\Http\*` from `gemvc/library` at **runtime** in real apps. When both packages are in `vendor/`, autoload resolves the real HTTP classes from the library.

This package ships **HTTP stubs** under `stubs/` for standalone PHPUnit/PHPStan only (`autoload-dev`). They are not used when the package is installed as a dependency of `gemvc/library`.

## Upgrading to 1.1.0

See [RELEASE_NOTES.md](RELEASE_NOTES.md#version-110--schema-validation-types).

**New `TypeChecker` types:** `decimal`, `decimal:P,S`, `hex`, `uuid`, `slug`, `positive_int`, `timestamp`, `jsonb` (alias of `json`).

```php
TypeChecker::check('decimal', '19.99');
TypeChecker::check('uuid', '550e8400-e29b-41d4-a716-446655440000');
TypeChecker::check('positive_int', '42', ['min' => 1, 'max' => 100]);
```

Requires `gemvc/library` **5.10.0+** for DB mapping and Request getters. Helper 1.1.0 adds validation only.

## Development

```bash
composer install
composer test
composer phpstan
```

## Release workflow (v1)

1. Copy this folder into a clone of `github.com/gemvc/helper`
2. Tag and publish **1.0.0** on Packagist
3. Update `gemvc/library` to require `gemvc/helper: ^1.0` and remove `src/helper/`

## v1.0 changes

- Lift-and-shift from `gemvc/library/src/helper/` (14 files, same namespace)
- `ProjectHelper::getLibrarySystemPagesPath()` resolves `gemvc/library` via `InstalledVersions`

Future cleanup (TraceKit legacy removal, etc.) happens in this package only.

## Push to GitHub (your workflow)

1. Create repo `github.com/gemvc/helper`
2. Copy **all contents** of this staging folder into the clone (repo root = package root; local folder name `gemvc-helper/` in the monorepo is only for staging)
3. `composer install` then `composer test` && `composer phpstan`
4. Tag **v1.0.0** and publish to Packagist as `gemvc/helper`
5. In `gemvc/library` release **5.8.1**: add `"gemvc/helper": "^1.0"`, remove `src/helper/` and `Gemvc\Helper\` autoload, run `composer update` and full test suite
