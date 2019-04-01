# Mojang API

This package provides easy access to the Minecraft related API of Mojang.
The library is built on the top of the [Guzzle HTTP client](https://github.com/guzzle/guzzle),
has custom errors handler and automatic retry in case of problems with Mojang.

> Please note that this is not a complete implementation of all available APIs. 
  If you don't find the method you need, [open Issue](https://github.com/elyby/mojang-api/issues/new)
  or [submit a PR](https://github.com/elyby/mojang-api/compare) with the implementation.

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-build-status]][link-build-status]

## Installation

To install, use composer:

```bash
composer require ely/mojang-api
```

## Usage

To get the configured `Api` object right away, just use the static `create()` method:

```php
<?php
$api = \Ely\Mojang\Api::create();
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

[link-packagist]: https://packagist.org/packages/ely/mojang-api
[link-contributors]: ../../contributors
[link-downloads]: https://packagist.org/packages/ely/mojang-api/stats
[link-build-status]: https://travis-ci.org/elyby/mojang-api
