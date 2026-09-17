# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Applied Rector: `declare(strict_types=1)` added to the config file and test files, repeated key checks in `ConfigryptService` simplified with a strict `in_array()`.
- `phpstan.neon`: removed orphaned `ignoreErrors` entries pointing to non-existent paths (`src/Mappers`, `src/Exporters`, `src/ConfigryptManager.php`, `tests/Feature`) and unmatched Pest patterns; only the config `env()` exception remains.
- GitHub Actions: `actions/checkout` bumped to v5, `softprops/action-gh-release` bumped to v2.

## [v1.6.0] - 2026-09-17

### Added

- Laravel 13 support (`illuminate/*` `^12.0|^13.0`).
- CI test matrix now covers PHP 8.3 / 8.4 and Laravel 12 / 13 (Testbench 10 / 11), with `prefer-lowest` and `prefer-stable`.

### Changed

- PHP 8.3 is now the minimum required version.
- Development dependencies updated: Pest `^3.8|^4.0`, Pest Laravel plugin `^3.2|^4.0`, Orchestra Testbench `^10.0|^11.0`.
- Code quality workflow now runs Pint alongside PHPStan.
- Release workflow now tests against Laravel 13 / Testbench 11.

### Fixed

- Facade docblocks aligned with the current Pint `fully_qualified_strict_types` rule.

## [v1.5.0] - 2025-08-13

- Added `ConfigValue` class for lazy configuration decryption.

## [v1.0.0] - 2025-07-25

- Initial release.

[v1.6.0]: https://github.com/Grazulex/laravel-configrypt/compare/v1.5.0...v1.6.0
[v1.5.0]: https://github.com/Grazulex/laravel-configrypt/compare/v1.0.0...v1.5.0
[v1.0.0]: https://github.com/Grazulex/laravel-configrypt/releases/tag/v1.0.0
