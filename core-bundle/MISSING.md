Missing features
================

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
