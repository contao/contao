# Contao 4 installation bundle

[![](https://img.shields.io/travis/contao/installation-bundle/master.svg?style=flat-square)](https://travis-ci.org/contao/installation-bundle/)
[![](https://img.shields.io/coveralls/contao/installation-bundle/master.svg?style=flat-square)](https://coveralls.io/github/contao/installation-bundle)
[![](https://img.shields.io/packagist/v/contao/installation-bundle.svg?style=flat-square)](https://packagist.org/packages/contao/installation-bundle)
[![](https://img.shields.io/packagist/dt/contao/installation-bundle.svg?style=flat-square)](https://packagist.org/packages/contao/installation-bundle)

Contao is an Open Source PHP Content Management System for people who want a
professional website that is easy to maintain. Visit the [project website][1]
for more information.

The installation bundle is required to install and update Contao 4.

## Installation

Run the following command in your project directory:

```bash
php composer.phar require contao/installation-bundle "^4.4"
```

## Activation

Adjust to your `app/AppKernel.php` file:

```php
// app/AppKernel.php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...
            new Contao\InstallationBundle\ContaoInstallationBundle(),
        ];
    }
}
```

## License

Contao is licensed under the terms of the LGPLv3.

## Getting support

Visit the [support page][2] to learn about the available support options.

[1]: https://contao.org
[2]: https://contao.org/en/support.html
