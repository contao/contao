# Contao 4 listing bundle

Contao is an Open Source PHP Content Management System for people who want a
professional website that is easy to maintain. Visit the [project website][1]
for more information.

The listing bundle allows to list arbitrary data in the Contao 4 front end.

## Installation

Run the following command in your project directory:

```bash
php composer.phar require contao/listing-bundle "^4.4"
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
            new Contao\ListingBundle\ContaoListingBundle(),
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
