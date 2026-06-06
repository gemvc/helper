# gemvc/helper v1.0.0

**Release date:** 6 June 2026  
**Tag:** `v1.0.0`  
**Packagist:** [`gemvc/helper`](https://packagist.org/packages/gemvc/helper)

---

## Summary

First stable release of **gemvc/helper** — helper utilities extracted from `gemvc/library` into a dedicated Composer package. The `Gemvc\Helper\` namespace and public APIs are unchanged, so existing GEMVC applications can adopt this release without code changes once `gemvc/library` depends on it.

This package is part of the internal GEMVC ecosystem. Install it via `gemvc/library`; do not use it as a standalone utility library in unrelated PHP projects.

---

## What's included

14 helper classes, lifted from `gemvc/library/src/helper/`:

| Class | Purpose |
|-------|---------|
| `ProjectHelper` | Project root, `.env`, URLs, library paths |
| `CryptHelper` | Password hashing (Argon2i) |
| `TypeChecker` / `TypeHelper` | Request schema type validation |
| `FileHelper` | AES-256-CBC file encryption |
| `ImageHelper` | WebP conversion |
| `JsonHelper`, `StringHelper`, `WebHelper` | General utilities |
| `ServerMonitorHelper`, `NetworkHelper` | Monitoring metrics |
| `ChatGptClient`, `TraceKitToolkit`, `TraceKitModel` | Framework-integrated helpers (require `gemvc/library` at runtime) |

---

## Highlights

### Standalone package, same namespace

- Namespace remains `Gemvc\Helper\` — no import changes for consumers.
- PSR-4 autoload: `Gemvc\Helper\` → `src/`.

### No circular Composer dependency

- `gemvc/library` **requires** `gemvc/helper`.
- `gemvc/helper` does **not** require `gemvc/library`, avoiding a `library → helper → library` cycle.
- Classes that use `Gemvc\Http\*` resolve those classes from `gemvc/library` when both packages are installed.

### `ProjectHelper` path resolution

- `ProjectHelper::getLibrarySystemPagesPath()` now resolves `gemvc/library` via Composer's `InstalledVersions` API instead of assuming a monorepo layout.

### Lean dist installs

Tests and dev tooling are excluded from Packagist dist archives:

- `.gitattributes` `export-ignore` for `tests/`, `stubs/`, `phpunit.xml`, `phpstan.neon`, `.phpunit.cache`, and CI config.
- Matching `archive.exclude` entries in `composer.json`.

Consumers who install via Composer (default dist) receive only production source under `vendor/gemvc/helper/`.

---

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | `^8.2` |
| Composer runtime API | `^2.2` |
| symfony/dotenv | `^6.4` or `^7.2` |

**Suggested extensions:**

- `ext-gd` — `ImageHelper` WebP conversion
- `ext-openssl` — `FileHelper` encryption and `CryptHelper`

---

## Installation

For GEMVC applications, install the framework (which pulls in this package automatically):

```bash
composer require gemvc/library
```

To require this package explicitly (e.g. during library migration):

```bash
composer require gemvc/helper:^1.0
```

---

## Migration from `gemvc/library` monorepo

If you currently use helpers bundled inside `gemvc/library`:

1. Upgrade to **`gemvc/library` 5.8.1** (or later), which requires `gemvc/helper: ^1.0`.
2. Remove any direct references to `src/helper/` in the library tree — helpers now live in `vendor/gemvc/helper/src/`.
3. No application code changes are required; `use Gemvc\Helper\...` statements stay the same.

---

## Development

Clone the repository and run:

```bash
composer install
composer test
composer phpstan
```

The repo includes HTTP stubs under `stubs/` (dev autoload only) so unit tests and PHPStan can run without `gemvc/library` installed.

---

## Known limitations

- **PHPStan:** 4 pre-existing static-analysis issues carried over from the original `src/helper/` code in `gemvc/library`. Behaviour is unchanged; cleanup is planned in a future release.
- **TraceKit legacy:** `TraceKitToolkit` and `TraceKitModel` remain for compatibility. Future removal will happen in this package only.
- **Runtime coupling:** `ChatGptClient` and TraceKit classes depend on `Gemvc\Http\*` from `gemvc/library` at runtime.

---

## Full changelog

### Added

- Initial release of `gemvc/helper` on Packagist and GitHub.
- 14 helper classes under `Gemvc\Helper\`.
- Unit and integration test suite (14 test files).
- PHPUnit and PHPStan configuration for standalone package development.
- `.gitattributes` and `composer.json` `archive.exclude` to omit dev files from dist installs.

### Changed

- `ProjectHelper::getLibrarySystemPagesPath()` uses `InstalledVersions::getInstallPath('gemvc/library')`.

### Unchanged

- Public class APIs and namespace (`Gemvc\Helper\`).
- Helper behaviour as shipped in `gemvc/library` prior to extraction.

---

## Links

- Repository: [github.com/gemvc/helper](https://github.com/gemvc/helper)
- Issues: [github.com/gemvc/helper/issues](https://github.com/gemvc/helper/issues)
- GEMVC: [gemvc.de](https://gemvc.de)
