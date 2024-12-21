<p align="center"><img src="https://contao.org/files/contao/logo/contao-logo-corporate.svg" alt></p>

<p align="center">
<a href="https://github.com/contao/contao/actions"><img src="https://github.com/contao/contao/actions/workflows/ci.yml/badge.svg?branch=5.3" alt></a>
<a href="https://codecov.io/gh/contao/contao"><img src="https://codecov.io/gh/contao/contao/branch/5.3/graph/badge.svg" alt></a>
<a href="https://packagist.org/packages/contao/contao"><img src="https://img.shields.io/packagist/v/contao/contao.svg" alt></a>
</p>

## About

Contao is a powerful open source CMS that allows you to create professional websites and scalable web applications.
Visit the [project website][1] for more information.

## Purpose

The purpose of this package is to develop the Contao bundles in a monorepo. Use it when you want to create a pull
request or report an issue.

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

Replace `<directory>` with the directory where you want to install the Managed Edition (use `.` for the current
directory). Replace `<branch>` with `5.x-dev` if you want to add a new feature, or with `<lts-version>.x-dev` (currently
`5.3.x-dev`) if you want to fix a bug.

Then adjust the `require` section in your `composer.json` file, so Composer loads the monorepo instead of the individual
bundles:

```json
"require": {
    "php": "^8.1",
    "contao/contao": "5.x-dev"
},
```

Again, use `5.x-dev` if you want to add a new feature or `<lts-version>.x-dev` if you want to fix a bug.

Next, install the dependencies:

```bash
composer update
```

Composer automatically clones the Git repository into the `vendor/contao/contao` folder. You can complete the setup by
running `vendor/bin/contao-setup` on the command line.

Any changes you make in `vendor/contao/contao` will be tracked via Git, so you can submit your pull request directly
from your application.

## Running scripts

First install the code quality tools in `vendor/contao/contao`:

```bash
composer bin all install
```

Then run the code quality scripts via Composer:

```bash
composer all
```

You can also run the scripts separately:

```bash
composer rector
composer ecs
composer service-linter
composer monorepo-tools
composer unit-tests
composer functional-tests
composer phpstan
composer depcheck
```

Use the `--` argument to pass additional flags to the underlying commands:

```bash
composer unit-tests -- --filter CoreBundle
composer ecs -- --clear-cache
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

Then run the functional tests via Composer:

```bash
composer functional-tests
```

## Node.js

To build the assets, you need a Node.js version >= 18.12. Then run these commands:

```bash
npm ci
npm run build
```

## End-to-end tests

The Contao end-to-end tests are availabe as an [NPM package][3]. You can install and run them like this:

```bash
npm install contao-e2e-tests --save-dev
npx contao-e2e-tests
```

## License

Contao is licensed under the terms of the LGPLv3.

## Getting support

Visit the [support page][4] to learn about the available support options.

[1]: https://contao.org
[2]: https://github.com/contao/managed-edition
[3]: https://www.npmjs.com/package/contao-e2e-tests
[4]: https://to.contao.org/support
