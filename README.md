# Contao 4 bundles

This is a monorepo holding the official Contao 4 bundles.

Contao is an Open Source PHP Content Management System for people who want a
professional website that is easy to maintain. Visit the [project website][1]
for more information.

## Purpose

The purpose of this package is to develop the Contao 4 bundles. Use it if you
e.g. want to create a pull request or if you want to report an issue.

The monorepo is split into separate packages automatically:

 * [CalendarBundle](https://github.com/contao/calendar-bundle)
 * [CommentsBundle](https://github.com/contao/comments-bundle)
 * [CoreBundle](https://github.com/contao/core-bundle)
 * [FaqBundle](https://github.com/contao/faq-bundle)
 * [InstallationBundle](https://github.com/contao/installation-bundle)
 * [ListingBundle](https://github.com/contao/listing-bundle)
 * [ManagerBundle](https://github.com/contao/manager-bundle)
 * [NewsBundle](https://github.com/contao/news-bundle)
 * [NewsletterBundle](https://github.com/contao/newsletter-bundle)

**Please do not use `contao/contao` in production** but use the split packages
instead.

## Test setup

To create a Pull Request to this monorepository and test your changes within
a running Contao 4 application it is easiest to use the [Contao Managed Edition][3].
Start by installing it in your current directory running

```
$ composer create-project --no-install contao/managed-edition <version> .
```

If you want to work on the latest version you can omit `<version>`, otherwise specify it (e.g. `4.4`).

Then replace the `require` section in your `composer.json` so that it does not require the individual bundles
but this monorepository instead:

```json
"require": {
  "php": "^7.1",
  "contao/contao": "dev-master"
},
```

Use `dev-master` for the latest version if you want to introduce new features. Use `dev-<lts-version>` (currently
`dev-4.4`) if you want to contribute a bugfix.
Then install the dependencies using

```
$ composer update
```

Because you required the monorepository `contao/contao` using the `dev-` prefix, Composer will always install the
monorepository from `source` which means it will clone this git repository into `vendor/contao/contao`.

You can now finish your setup by visiting `https://your-domain.local/contao/install` and start working with Contao 4.
All the changes you make within `vendor/contao/contao` can now easily be tracked using git and you can submit your
Pull Request directly from within your working application!

## Running scripts

You can use the `run` command to run scripts in all bundles:

```bash
./run phpunit
./run php-cs-fixer
```

## License

Contao is licensed under the terms of the LGPLv3.

## Getting support

Visit the [support page][2] to learn about the available support options.

[1]: https://contao.org
[2]: https://contao.org/en/support.html
[3]: https://github.com/contao/managed-edition
