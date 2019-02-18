# Contao 4 core bundle

[![](https://img.shields.io/packagist/v/contao/core-bundle.svg?style=flat-square)](https://packagist.org/packages/contao/core-bundle)
[![](https://img.shields.io/packagist/dt/contao/core-bundle.svg?style=flat-square)](https://packagist.org/packages/contao/core-bundle)

Contao is an Open Source PHP Content Management System for people who want a
professional website that is easy to maintain. Visit the [project website][1]
for more information.

Contao 4 has been designed as a [Symfony][2] bundle, which can be used to add
CMS functionality to any Symfony application. If you do not have an existing
Symfony application yet, we recommend using the [Contao managed edition][3] as
basis for your application.

## Prerequisites

The Contao core bundle has a recipe in the [symfony/recipes-contrib][6]
repository. Be sure to either enable contrib recipes for your project by
running the following command or follow the instructions to use the contrib
recipe during the installation process.

```
composer config extra.symfony.allow-contrib true
```

Add the `contao-component-dir` to the `extra` section of your `composer.json`
file.

```
composer config extra.contao-component-dir assets
```

## Installation

Install Contao and all its dependencies by executing the following command:

```
composer require \
    contao/core-bundle:4.7.* \
    contao/installation-bundle:^4.7 \
    php-http/guzzle6-adapter:^1.1
```

Note that you can exchange the `php-http/guzzle6-adapter` package with any
other [HTTP client implementation][4]. If you already have an HTTP client
implementation, you can omit the package entirely.

## Configuration

Configure the `DATABASE_URL` in your environment, either using environment
variables or by using the [Dotenv component][7].

Enable ESI in the `config/packages/framework.yaml` file.

```yaml
framework:
    esi: true
```

Add the Contao routes to your `config/routing.yaml` file, and be sure to load
the `ContaoCoreBundle` at the very end, so the catch all route does not catch
your application routes.

```yml
ContaoCoreBundle:
    resource: "@ContaoCoreBundle/Resources/config/routing.yml"
```

Edit your `app/config/security.yml` file and merge all the `providers`,
`encoders`, `firewalls` and `access_control` sections:

```yml
security:
    providers:
        contao.security.backend_user_provider:
            id: contao.security.backend_user_provider

        contao.security.frontend_user_provider:
            id: contao.security.frontend_user_provider

    encoders:
        Contao\User:
            algorithm: bcrypt

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt|error)|css|images|js)/
            security: false

        contao_install:
            pattern: ^/contao/install$
            security: false

        contao_backend:
            entry_point: contao.security.entry_point
            request_matcher: contao.routing.backend_matcher
            provider: contao.security.backend_user_provider
            user_checker: contao.security.user_checker
            anonymous: ~
            switch_user: true
            logout_on_user_change: true

            contao_login:
                login_path: contao_backend_login
                check_path: contao_backend_login
                default_target_path: contao_backend
                success_handler: contao.security.authentication_success_handler
                failure_handler: contao.security.authentication_failure_handler
                remember_me: false

            two_factor:
                auth_form_path: contao_backend_login
                check_path: contao_backend_two_factor
                auth_code_parameter_name: verify

            logout:
                path: contao_backend_logout
                target: contao_backend_login
                handlers:
                    - contao.security.logout_handler

        contao_frontend:
            request_matcher: contao.routing.frontend_matcher
            provider: contao.security.frontend_user_provider
            user_checker: contao.security.user_checker
            anonymous: ~
            switch_user: false
            logout_on_user_change: true

            contao_login:
                login_path: contao_frontend_login
                check_path: contao_frontend_login
                default_target_path: contao_root
                failure_path: contao_root
                success_handler: contao.security.authentication_success_handler
                failure_handler: contao.security.authentication_failure_handler
                remember_me: true
                use_forward: true

            remember_me:
                secret: '%secret%'
                remember_me_parameter: autologin
                token_provider: contao.security.database_token_provider

            logout:
                path: contao_frontend_logout
                target: contao_root
                handlers:
                    - contao.security.logout_handler
                success_handler: contao.security.logout_success_handler

    access_control:
        - { path: ^/contao/login$, roles: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/contao/logout$, roles: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/contao(/|$), roles: ROLE_USER }
```

The Contao core-bundle as well as the installation-bundle are now installed and
activated. Use the Contao install tool to complete the installation by opening
the `/contao/install` route in your browser.

## License

Contao is licensed under the terms of the LGPLv3.

## Getting support

Visit the [support page][5] to learn about the available support options.

[1]: https://contao.org
[2]: https://symfony.com
[3]: https://github.com/contao/managed-edition
[4]: https://packagist.org/providers/php-http/client-implementation
[5]: https://contao.org/en/support.html
[6]: https://github.com/symfony/recipes-contrib
[7]: http://symfony.com/doc/current/components/dotenv.html
