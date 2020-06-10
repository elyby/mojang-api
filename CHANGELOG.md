# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- [Check if security questions are needed](https://wiki.vg/Mojang_API#Check_if_security_questions_are_needed) endpoint.
- [Get list of questions](https://wiki.vg/Mojang_API#Get_list_of_questions) endpoint.
- [Send back the answers](https://wiki.vg/Mojang_API#Send_back_the_answers) endpoint.
- [Statistics](https://wiki.vg/Mojang_API#Statistics) endpoint.

### Changed
- Changed the threshold value for [Playernames -> UUIDs](https://wiki.vg/Mojang_API#Playernames_-.3E_UUIDs) endpoint
  from `100` to `10`.

## [0.2.0] - 2019-05-07
### Added
- This CHANGELOG.md file.
- `\Ely\Mojang\Api::setClient()` method to override default HTTP client.
- [API Status](https://wiki.vg/Mojang_API#API_Status) endpoint.
- [UUID to Name history](https://wiki.vg/Mojang_API#UUID_-.3E_Name_history) endpoint.
- [Playernames -> UUIDs](https://wiki.vg/Mojang_API#Playernames_-.3E_UUIDs) endpoint.
- [Change Skin](https://wiki.vg/Mojang_API#Change_Skin) endpoint.
- [Reset Skin](https://wiki.vg/Mojang_API#Reset_Skin) endpoint.
- [Blocked Servers](https://wiki.vg/Mojang_API#Blocked_Servers) endpoint.
- [Refresh](https://wiki.vg/Authentication#Refresh) endpoint.
- [Signout](https://wiki.vg/Authentication#Signout) endpoint.
- [Invalidate](https://wiki.vg/Authentication#Invalidate) endpoint.

### Changed
- The constructor no longer has arguments.

### Fixed
- Change `static` to `self` in `\Ely\Mojang\Response\Properties\Factory` to allow its extending.
- Fix `validate` endpoint URL.

### Removed
- `\Ely\Mojang\Api::create()` static method. Use constructor instead.

## [0.1.0] - 2019-04-01
### Added
- Initial implementation

[Unreleased]: https://github.com/elyby/mojang-api/compare/0.2.0...HEAD
[0.2.0]: https://github.com/elyby/mojang-api/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/elyby/mojang-api/releases/tag/0.1.0
