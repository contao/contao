Contao 4 core bundle
====================

[![](https://img.shields.io/travis/contao/core-bundle.svg?style=flat-square)](https://travis-ci.org/contao/core-bundle/)
[![](https://img.shields.io/scrutinizer/g/contao/core-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/contao/core-bundle/)
[![](https://img.shields.io/scrutinizer/coverage/g/contao/core-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/contao/core-bundle/)

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
    "contao/core-bundle": "~4.0"
}
```

Edit your `app/config/config.yml` file and add the following:

```yml
# Contao configuration
contao:
    prepend_locale: "%prepend_locale%"
    url_suffix:     "%url_suffix%"
    upload_path:    "%upload_path%"
    encryption_key: "%kernel.secret%"
```

Add the Contao routes to your `app/config/routing.yml` file:

```yml
ContaoCoreBundle:
    resource: "@ContaoCoreBundle/Controller"
    type: annotation

# Redirect /contao/ to /contao
contao_backend_redirect:
    path: /contao/
    defaults:
        _scope: backend
        _controller: FrameworkBundle:Redirect:redirect
        route: contao_backend
        permanent: true

# The fallback route must be the last one!
contao_frontend:
    resource: .
    type: contao_frontend
```

Add the following entries to your `app/config/security.yml` file:

```yml
security:
    providers:
        contao.security.user_provider:
            id: contao.security.user_provider

    firewalls:
        install:
            pattern: ^/contao/install
            security: false

        backend:
            pattern: ^/contao
            stateless: true
            simple_preauth:
                authenticator: contao.security.authenticator

        frontend:
            pattern: ~
            stateless: true
            simple_preauth:
                authenticator: contao.security.authenticator
```


Meta package
------------

There is a meta package at [contao/contao][4], which you can require in your
`composer.json` instead of requiring all the bundles separately:

```json
"require": {
    "contao/contao": "~4.0"
}
```


[1]: https://contao.org
[2]: http://symfony.com/
[3]: https://github.com/contao/standard-edition
[4]: https://github.com/contao/contao
