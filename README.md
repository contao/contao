<p align="center"><img src="https://contao.org/files/contao/logo/contao-logo-corporate.svg"></p>

<p align="center">
<a href="https://github.com/contao/contao/actions"><img src="https://img.shields.io/github/workflow/status/contao/contao/CI/4.4.svg" alt="GitHub"></a>
<a href="https://codecov.io/gh/contao/contao"><img src="https://img.shields.io/codecov/c/gh/contao/contao/4.4.svg" alt="Codecov"></a>
<a href="https://packagist.org/packages/contao/contao"><img src="https://img.shields.io/packagist/v/contao/contao.svg" alt="Packagist"></a>
</p>

## About

Contao is a powerful open source CMS that allows you to create professional
websites and scalable web applications. Visit the [project website][1] for more
information.

## Purpose

The purpose of this package is to develop the Contao bundles. Use it if you
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

**Please do not use `contao/contao` in production**! Use the split packages
instead.

## Development

To create a pull request and to test your changes within a running Contao
application, it is the easiest to use the [Contao Managed Edition][2]. Start by
installing it in your current directory:

```bash
composer create-project --no-install contao/managed-edition <directory> <branch>
```

Replace `<directory>` with the directory you want to install the Managed
Edition in (use `.` for the current one). Replace `<branch>` with `dev-master`
if you want to add a new feature or with `<lts-version>.x-dev` (currently
`4.4.x-dev`) if you want to fix a bug.

Then adjust the `require` section in your `composer.json` file so Composer
loads the monorepo instead of the individual bundles:

```json
"require": {
    "php": "^7.2",
    "contao/contao": "dev-master"
},
```

Again, use `dev-master` if you want to add a new feature or
`<lts-version>.x-dev` if you want to fix a bug.

Next, install the dependencies:

```bash
composer update
```

Composer will automatically clone the Git repo into the `vendor/contao/contao`
folder. You can finish your setup by opening
`https://your-domain.local/contao/install` in your browser.

All the changes you make in `vendor/contao/contao` are be tracked via Git, so
you can submit your pull request directly from within your application.

## Running scripts

You can use the `run` script to run the code quality scripts:

```bash
./run phpunit
./run php-cs-fixer
```

## License

Contao is licensed under the terms of the LGPLv3.

## Getting support

Visit the [support page][3] to learn about the available support options.

[1]: https://contao.org
[2]: https://github.com/contao/managed-edition
[3]: https://contao.org/en/support.html
