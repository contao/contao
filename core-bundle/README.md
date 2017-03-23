Contao 4 core bundle
====================

[![](https://img.shields.io/travis/contao/core-bundle/master.svg?style=flat-square)](https://travis-ci.org/contao/core-bundle/)
[![](https://img.shields.io/scrutinizer/g/contao/core-bundle/master.svg?style=flat-square)](https://scrutinizer-ci.com/g/contao/core-bundle/)
[![](https://img.shields.io/coveralls/contao/core-bundle/master.svg?style=flat-square)](https://coveralls.io/github/contao/core-bundle)
[![](https://img.shields.io/packagist/v/contao/core-bundle.svg?style=flat-square)](https://packagist.org/packages/contao/core-bundle)
[![](https://img.shields.io/packagist/dt/contao/core-bundle.svg?style=flat-square)](https://packagist.org/packages/contao/core-bundle)

Contao is an Open Source PHP Content Management System for people who want a
professional website that is easy to maintain. Visit the [project website][1]
for more information.

Contao 4 has been designed as a [Symfony][2] bundle, which can be used to add
CMS functionality to any Symfony application. If you do not have an existing
Symfony application yet, we recommend using the [Contao standard edition][3] as
basis for your application.


Installation
------------

Edit your `composer.json` file and add the following requirement:

```json
"require": {
    "contao/core-bundle": "~4.2"
}
```

Edit your `app/config/config.yml` file and add the following:

```yml
# Contao configuration
contao:
    # Required parameters
    prepend_locale: true
    encryption_key: "%kernel.secret%"

    # Optional parameters
    url_suffix:           .html
    upload_path:          files
    csrf_token_name:      contao_csrf_token
    pretty_error_screens: true
    error_level:          8183 # E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED
```

Add the Contao routes to your `app/config/routing.yml` file:

```yml
ContaoCoreBundle:
    resource: "@ContaoCoreBundle/Resources/config/routing.yml"
```

Add the following entries to your `app/config/security.yml` file:

```yml
imports:
    - { resource: "@ContaoCoreBundle/Resources/config/security.yml" }
```


Meta package
------------

There is a meta package at [contao/contao][4], which you can require in your
`composer.json` to install all the default bundles at once:

```json
"require": {
    "contao/contao": "~4.2"
}
```

This is the same as:

```json
"require": {
    "contao/calendar-bundle": "~4.2",
    "contao/comments-bundle": "~4.2",
    "contao/core-bundle": "~4.2",
    "contao/faq-bundle": "~4.2",
    "contao/installation-bundle": "~1.1",
    "contao/listing-bundle": "~4.2",
    "contao/news-bundle": "~4.2",
    "contao/newsletter-bundle": "~4.2"
}
```


[1]: https://contao.org
[2]: http://symfony.com/
[3]: https://github.com/contao/standard-edition
[4]: https://github.com/contao/contao
