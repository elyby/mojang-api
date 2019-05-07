# Mojang API

This package provides easy access to the Minecraft related API of Mojang.
The library is built on the top of the [Guzzle HTTP client](https://github.com/guzzle/guzzle),
has custom errors handler and automatic retry in case of problems with Mojang.

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-build-status]][link-build-status]
[![Scrutinizer Code Quality][ico-code-quality]][link-scruntinizer-project]
[![Code Coverage][ico-code-coverage]][link-scruntinizer-project]

## Installation

To install, use composer:

```bash
composer require ely/mojang-api
```

## Usage

To start using this library just create a new `Api` class instance and call the necessary endpoint:

```php
<?php
$api = new \Ely\Mojang\Api();
$response = $api->usernameToUUID('erickskrauch');
echo $response->getId();
```

## Testing

```bash
$ ./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

This package was designed and developed within the [Ely.by](http://ely.by) project team. We also thank all the
[contributors](link-contributors) for their help.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/ely/mojang-api.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/ely/mojang-api.svg?style=flat-square
[ico-build-status]: https://img.shields.io/travis/elyby/mojang-api/master.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/elyby/mojang-api.svg?style=flat-square
[ico-code-coverage]: https://img.shields.io/scrutinizer/coverage/g/elyby/mojang-api.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/ely/mojang-api
[link-contributors]: ../../contributors
[link-downloads]: https://packagist.org/packages/ely/mojang-api/stats
[link-build-status]: https://travis-ci.org/elyby/mojang-api
[link-scruntinizer-project]: https://scrutinizer-ci.com/g/elyby/mojang-api
