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

Edit your `composer.json` file and add the following:

```json
"require": {
    "contao/core-bundle": "^4.4"
}
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


Activation
-------------

Remove the `parameters.yml` import from your `app/config/config.yml` file:

```yml
imports:
    - { resource: parameters.yml } # <-- remove this line
    - { resource: security.yml }
```

Then add the following to your `app/AppKernel.php` file:

```php
// app/AppKernel.php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Knp\Bundle\TimeBundle\KnpTimeBundle(),
            new Contao\CoreBundle\ContaoCoreBundle(),
        );
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

Add the Contao routes to your `app/config/routing.yml` file:

```yml
ContaoCoreBundle:
    resource: "@ContaoCoreBundle/Resources/config/routing.yml"
```

Import the Contao `security.yml` file in your `app/config/security.yml` file:

```yml
imports:
    - { resource: "@ContaoCoreBundle/Resources/config/security.yml" }
```

Edit your `app/config/config.yml` file and add the following:

```yml
# Contao configuration
contao:
    # Required parameters
    prepend_locale: "%prepend_locale%"

    # Optional parameters
    root_dir:             "%kernel.root_dir%/.."
    encryption_key:       "%kernel.secret%"
    url_suffix:           .html
    upload_path:          files
    csrf_token_name:      contao_csrf_token
    pretty_error_screens: true
    error_level:          8183 # E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED
    image:
        bypass_cache:     false
        target_path:      assets/images
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


License
-------

Contao is licensed under the terms of the LGPLv3.


Getting support
---------------

Visit the [support page][4] to learn about the available support options.


[1]: https://contao.org
[2]: https://symfony.com
[3]: https://github.com/contao/standard-edition
[4]: https://contao.org/en/support.html
