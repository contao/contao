Missing features
================

### Upgrade vom Contao 3

At the moment, upgrading from an existing Contao 3 installation is not fully
supported. There have been a lot of key changes (see UPGRADE.md), which still
need to be added to the version 4 update routine.


### Events and hooks

We are planning to replace the Contao hooks with the Symfony event dispatcher.
You can track the changes here: https://github.com/contao/core-bundle/pull/204


### Twig

It is possible to use Twig templates in Contao 4.0, however, the core still
uses the PHP engine for the time being.

We are planning to migrate to the Twig engine in the future, which also
involves adding a back end Twig template editor, which automatically copies
templates to `app/Resources/views`.


### Bundle autoloading

Right now, the `AppKernel` class and the configuration files have to be
adjusted manually if a new bundle is installed. We are looking for a more
convenient solution here, maybe based on the Composer package manager.
