# Contao 4 maker bundle

[![](https://img.shields.io/packagist/v/contao/maker-bundle.svg?style=flat-square)](https://packagist.org/packages/contao/maker-bundle)
[![](https://img.shields.io/packagist/dt/contao/maker-bundle.svg?style=flat-square)](https://packagist.org/packages/contao/maker-bundle)

The maker bundle allows you to generate content elements, front end modules,
event listener, callbacks and hooks using interactive commands.

Contao is an Open Source PHP Content Management System for people who want a
professional website that is easy to maintain. Visit the [project website][1]
for more information.

## Installation

Run this command to install and enable the bundle in your application:

```
composer require contao/maker-bundle --dev
```

## Usage

This bundle provides several commands under the `make:` namespace. You can list
them all with the following command:

```
php vendor/bin/contao-console list make:contao

Available commands for the "make:contao" namespace:
  make:contao:content-element  Creates a new content element
  make:contao:dca-callback     Creates a new DCA callback listener
  make:contao:event-listener   Creates a new event listener
  make:contao:frontend-module  Creates a new front end module
  make:contao:hook             Creates a new hook listener
```

## License

Contao is licensed under the terms of the LGPLv3.

## Getting support

Visit the [support page][2] to learn about the available support options.

[1]: https://contao.org
[2]: https://to.contao.org/support
