<p align="center"><img src="https://contao.org/files/contao/logo/contao-logo-corporate.svg"></p>

<p align="center">
<a href="https://github.com/contao/contao/actions"><img src="https://github.com/contao/contao/actions/workflows/ci.yml/badge.svg?branch=5.2" alt></a>
<a href="https://codecov.io/gh/contao/contao"><img src="https://codecov.io/gh/contao/contao/branch/5.2/graph/badge.svg" alt></a>
<a href="https://packagist.org/packages/contao/contao"><img src="https://img.shields.io/packagist/v/contao/contao.svg" alt></a>
</p>

## About

Contao is a powerful open source CMS that allows you to create professional websites and scalable web applications.
Visit the [project website][1] for more information.

## Purpose

The purpose of this package is to develop the Contao bundles. Use it if you e.g. want to create a pull request or if you
want to report an issue.

The monorepo is automatically split into separate packages:

 * [CalendarBundle](https://github.com/contao/calendar-bundle)
 * [CommentsBundle](https://github.com/contao/comments-bundle)
 * [CoreBundle](https://github.com/contao/core-bundle)
 * [FaqBundle](https://github.com/contao/faq-bundle)
 * [ListingBundle](https://github.com/contao/listing-bundle)
 * [MakerBundle](https://github.com/contao/maker-bundle)
 * [ManagerBundle](https://github.com/contao/manager-bundle)
 * [NewsBundle](https://github.com/contao/news-bundle)
 * [NewsletterBundle](https://github.com/contao/newsletter-bundle)

**Please do not use `contao/contao` in production**! Use the split packages instead.

## Development

To create a pull request and to test your changes within a running Contao application, it is the easiest to use the
[Contao Managed Edition][2]. Start by installing it in your current directory:

```bash
composer create-project --no-install contao/managed-edition <directory> <branch>
```

Replace `<directory>` with the directory you want to install the Managed Edition in (use `.` for the current one).
Replace `<branch>` with `5.x-dev` if you want to add a new feature or with `<lts-version>.x-dev` (currently
`4.13.x-dev`) if you want to fix a bug.

Then adjust the `require` section in your `composer.json` file, so Composer loads the monorepo instead of the individual
bundles:

```json
"require": {
    "php": "^8.0",
    "contao/contao": "5.x-dev"
},
```

Again, use `5.x-dev` if you want to add a new feature or `<lts-version>.x-dev` if you want to fix a bug.

Next, install the dependencies:

```bash
composer update
```

Composer will automatically clone the Git repo into the `vendor/contao/contao` folder. You can finish the setup by
running `contao:setup` on the command line.

All the changes you make in `vendor/contao/contao` are tracked via Git, so you can submit your pull request directly
from within your application.

## Running scripts

First install the code quality tools:

```bash
composer bin all install
```

Then run the code quality scripts via Composer:

```bash
composer run all
```

You can also run the scripts separately:

```bash
composer run rector
composer run cs-fixer
composer run service-linter
composer run monorepo-tools
composer run unit-tests
composer run functional-tests
composer run phpstan
composer run require-checker
```

If you want to pass additional flags to the underlying commands, you can use the `--` argument:

```bash
composer run unit-tests -- --filter CoreBundle
composer run cs-fixer -- --clear-cache
```

## Functional tests

To set up the functional tests, create a database named `contao_test`:

```bash
mysql -e "CREATE DATABASE contao_test"
```

If your database uses credentials, copy the file `core-bundle/phpunit.xml.dist` to `core-bundle/phpunit.xml` and adjust
the following line:

```xml
<php>
    <env name="DATABASE_URL" value="mysql://root@localhost:3306/contao_test" />
</php>
```

Then run the functional tests via the `run` command:

```bash
composer run functional-tests
```

## End-to-end tests with Cypress

Install the [Symfony CLI][3] and the HTTPS CA if you haven't already:

```bash
symfony server:ca:install
```

Install Cypress:

```bash
yarn install
composer bin cypress install
```

Run the end-to-end tests without UI:

```bash
yarn cypress
```

Run the end-to-end tests with the Cypress test suite:

```bash
yarn cypress-ui
```

## License

Contao is licensed under the terms of the LGPLv3.

## Getting support

Visit the [support page][4] to learn about the available support options.

[1]: https://contao.org
[2]: https://github.com/contao/managed-edition
[3]: https://symfony.com/download#step-1-install-symfony-cli
[4]: https://to.contao.org/support
