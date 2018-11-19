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

To create a pull request and to test your changes within a running Contao 4
application, it is the easiest to use the [Contao managed edition][3]. Start by
installing it in your current directory:

```bash
composer create-project --no-install contao/managed-edition <branch> .
```

Replace `<branch>` with `dev-master` if you want to add a new feature or with
`dev-<lts-version>` (currently `dev-4.4`) if you want to fix a bug.

Then adjust the `require` section in your `composer.json` file so Composer loads
the monorepo instead of the individual bundles:

```json
"require": {
    "php": "^7.1",
    "contao/contao": "dev-master"
},
```

Again, use `dev-master` if you want to add a new feature or `dev-<lts-version>`
if you want to fix a bug.

Next install the dependencies:

```
composer update
```

Because we have required `contao/contao` with a `dev-` prefix, Composer will
automatically clone the Git repo into the `vendor/contao/contao` folder. You can
finish your setup by visiting `https://your-domain.local/contao/install`.

All the changes you make in `vendor/contao/contao` can be tracked via Git and
you can submit your pull request directly from within your application.

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
