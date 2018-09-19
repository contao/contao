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

## Installation

Edit your `composer.json` file and add the following:

```json
"require": {
    "contao/core-bundle": "4.4.*",
    "contao/installation-bundle": "^4.4",
    "php-http/guzzle6-adapter": "^1.1"
},
"config": {
    "component-dir": "assets"
},
"post-install-cmd": [
    "Contao\\CoreBundle\\Composer\\ScriptHandler::addDirectories",
    "Contao\\CoreBundle\\Composer\\ScriptHandler::generateSymlinks"
],
"post-update-cmd": [
    "Contao\\CoreBundle\\Composer\\ScriptHandler::addDirectories",
    "Contao\\CoreBundle\\Composer\\ScriptHandler::generateSymlinks"
]
```

Then run `php composer.phar update` to install the vendor files.

Note that you can exchange the `php-http/guzzle6-adapter` package with any
other [HTTP client implementation][4]. If you already have an HTTP client
implementation, you can omit the package entirely.

## Activation

Remove the `parameters.yml` import from your `app/config/config.yml` file:

```yml
imports:
    - { resource: parameters.yml } # <-- remove this line
    - { resource: security.yml }
```

Then adjust to your `app/AppKernel.php` file:

```php
// app/AppKernel.php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new Knp\Bundle\TimeBundle\KnpTimeBundle(),
            new Nelmio\CorsBundle\NelmioCorsBundle(),
            new Contao\CoreBundle\ContaoCoreBundle(),
            new Contao\InstallationBundle\ContaoInstallationBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $rootDir = $this->getRootDir();

        if (file_exists($rootDir.'/config/parameters.yml')) {
            $loader->load($rootDir.'/config/parameters.yml');
        }

        $loader->load($rootDir.'/config/config_'.$this->getEnvironment().'.yml');
    }
}
```

## Configuration

Add the Contao routes to your `app/config/routing.yml` file:

```yml
ContaoInstallationBundle:
    resource: "@ContaoInstallationBundle/Resources/config/routing.yml"

ContaoCoreBundle:
    resource: "@ContaoCoreBundle/Resources/config/routing.yml"
```

Edit your `app/config/security.yml` file:

```yml
security:
    providers:
        contao.security.user_provider:
            id: contao.security.user_provider

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt|error)|css|images|js)/
            security: false

        install:
            pattern: ^/(contao/install|install\.php)
            security: false

        backend:
            request_matcher: contao.routing.backend_matcher
            stateless: true
            simple_preauth:
                authenticator: contao.security.authenticator

        frontend:
            request_matcher: contao.routing.frontend_matcher
            stateless: true
            simple_preauth:
                authenticator: contao.security.authenticator
```

Edit your `app/config/config.yml` file:

```yml
parameters:
    prepend_locale: false # Set to true if you like, just has to be set

# Framework configuration
framework:
    esi: { enabled: true }
    translator: { fallbacks: ['%locale%'] }

# Contao configuration
contao:
    # Required parameters
    prepend_locale: "%prepend_locale%"

    # Optional parameters
    web_dir:              "%kernel.project_dir%/web"
    encryption_key:       "%secret%"
    url_suffix:           .html
    upload_path:          files
    csrf_token_name:      contao_csrf_token
    pretty_error_screens: true
    error_level:          8183 # E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED
    image:
        bypass_cache:     false
        target_dir:       "%kernel.project_dir%/assets/images"
        valid_extensions: ['jpg', 'jpeg', 'gif', 'png', 'tif', 'tiff', 'bmp', 'svg', 'svgz']
        imagine_options:
            jpeg_quality: 80
            interlace:    plane
    security:
        disable_ip_check: false
```

You can also overwrite any parameter stored in the `localconfig.php` file:

```yml
# Contao configuration
contao:
    localconfig:
        adminEmail: foo@bar.com
        dateFormat: Y-m-d
```

## License

Contao is licensed under the terms of the LGPLv3.

## Getting support

Visit the [support page][5] to learn about the available support options.

[1]: https://contao.org
[2]: https://symfony.com
[3]: https://github.com/contao/managed-edition
[4]: https://packagist.org/providers/php-http/client-implementation
[5]: https://contao.org/en/support.html
