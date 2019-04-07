# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.0] - no date
### Added
- This CHANGELOG.md file.
- `\Ely\Mojang\Api::setClient()` method to override default HTTP client.
- [API Status](https://wiki.vg/Mojang_API#API_Status) endpoint.

### Changed
- The constructor no longer has arguments.

### Fixed
- Change `static` to `self` in `\Ely\Mojang\Response\Properties\Factory` to allow its extending.

### Removed
- `\Ely\Mojang\Api::create()` static method. Use constructor instead.

## [0.1.0] - 2019-04-01
### Added
- Initial implementation

[Unreleased]: https://github.com/elyby/mojang-api/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/elyby/mojang-api/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/elyby/mojang-api/releases/tag/0.1.0