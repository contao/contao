# The Contao Maker Bundle

![CI](https://github.com/1up-lab/contao-maker-bundle/workflows/CI/badge.svg)
[![Total Downloads](https://poser.pugx.org/contao/maker-bundle/d/total.png)](https://packagist.org/packages/contao/maker-bundle)

The Contao Maker bundle allows you to generate Content Elements, Frontend Modules and
Hooks using interactive commands.

## Installation

Run this command to install and enable this bundle in your application:

```
composer require contao/maker-bundle --dev
```

## Usage

This bundle provides several commands under the `make:` namespace.
List them all executing this command:

```
⇢ php bin/console list make:contao
  [...]

  make:contao:content-element  Creates an empty content element
  make:contao:dca-callback     Creates a dca callback
  make:contao:event-listener   Creates an event listener for a Contao event
  make:contao:frontend-module  Creates an empty frontend module
  make:contao:hook             Creates a hook
```

## License

Contao is licensed under the terms of the LGPLv3.

## Getting support

Visit the [support page][2] to learn about the available support options.

[1]: https://contao.org
[2]: https://contao.org/en/support.html
