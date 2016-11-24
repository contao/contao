Deprecated features
===================

### Image service

The `Image` and `Picture` classes have been deprecated in favor of the image
service. Here are two examples of how to use the service:

```php
// Old syntax
$image = Image::get($objSubfiles->path, 80, 60, 'center_center');

// New syntax
$container = System::getContainer();
$rootDir = dirname($container->getParameter('kernel.root_dir'));

$image = $container
    ->get('contao.image.image_factory')
    ->create($rootDir.'/'.$objSubfiles->path, [80, 60, 'center_center'])
    ->getUrl($rootDir)
;
```

```php
// Old syntax
$image = Image::create($path, [400, 50, 'box'])
    ->executeResize()
    ->getResizedPath()
;

// New syntax
$container = System::getContainer();
$rootDir = dirname($container->getParameter('kernel.root_dir'));

$image = $container
    ->get('contao.image.image_factory')
    ->create(
        $rootDir.'/'.$path,
        (new ResizeConfiguration())
            ->setWidth(400)
            ->setHeight(50)
            ->setMode(ResizeConfiguration::MODE_BOX)
    )
    ->getUrl($rootDir)
;
```

For more information see: https://github.com/contao/image/blob/master/README.md


### FORM_FIELDS

Using the `FORM_FIELDS` mechanism to determine which form fields have been
submitted has been deprecated in Contao 4.0 and will no longer work in Contao
5.0. Make sure to always submit at least an empty string in your widget.

```html
<!-- Wrong: the input will only be submitted if checked -->
<input type="checkbox" name="foo" value="bar">

<!-- Right: the input will always be submitted -->
<input type="hidden" name="foo" value=""><input type="checkbox" name="foo" value="bar">
```


### Page handler without getResponse()

Using a custom page handler without a `getResponse()` method has been
deprecated in Contao 4.0 and will no longer work in Contao 5.0.


### VERSION and BUILD

The `VERSION` and `BUILD` constants have been deprecated in Contao 4.0 and will
be removed in Contao 5.0. Use the `kernel.packages` parameter instead.

```php
$packages = System::getContainer()->getParameter('kernel.packages');
$coreVersion = $packages['contao/core-bundle'];
```


### member_grouped.html5

Accessing the field groups via one of the following properties in the
`member_grouped.html5` template has been deprecated in Contao 4.0 and will no
longer work in Contao 5.0:

 * `$this->personal`
 * `$this->address`
 * `$this->contact`
 * `$this->login`
 * `$this->captcha`

Use `$this->categories` instead.


### "channel" token

Using the simple token "channel" in newsletter subscription mails has been
deprecated in Contao 4.0 and will no longer work in Contao 5.0. Use the
"channels" token instead.


### $this->arrCache

Using `$this->arrCache`, which is defined in the `System` class, has been
deprecated in Contao 4.0 and will no longer work in Contao 5.0. If you are
using it in your class, make sure to define it as property.


### $this->items in pagination templates

Using `$this->items` in pagination templates has been deprecated in Contao 4.0
and will no longer work in Contao 5.0. Use `$this->pages` instead.


### TL_SCRIPT_URL and TL_PLUGINS_URL

The constants `TL_SCRIPT_URL` and `TL_PLUGINS_URL` have been deprecated in
Contao 4.0 and will be removed in Contao 5.0. Use `TL_ASSETS_URL` instead.


### UnresolvableDependenciesException

The `UnresolvableDependenciesException` class has been deprecated in Contao 4.0
and will be removed in Contao 5.0.


### $this->language in TinyMCE config files

Using `$this->language` in TinyMCE configuration files has been deprecated in
Contao 4.0 and will no longer work in Contao 5.0. Use the static method
`Backend::getTinyMceLanguage()` instead.


### $GLOBALS['TL_LANGUAGE'] and $_SESSION['TL_LANGUAGE']

Using the globals `$GLOBALS['TL_LANGUAGE']` and `$_SESSION['TL_LANGUAGE']` has
been deprecated in Contao 4.0 and will no longer work in Contao 5.0. Use the
locale from the request object instead:

```php
$locale = System::getContainer()->get('request_stack')->getCurrentRequest()->getLocale();
```


### Request.Mixed (JavaScript)

Using the old Request.Mixed class instead of Request.Contao has been deprecated
in Contao 4.0 and will no longer work in Contao 5.0.


### "subpalette" event (JavaScript)

The "subpalette" event, which is currently fired when a subpalette is toggled
via Ajax, has been deprecated in Contao 4.0 and will be removed in Contao 5.0.
Subscribe to the "ajax_change" event instead.


### Session class

The `Session` class has been deprecated in Contao 4.0 and will be removed in
Contao 5.0. Use the session service instead:

```php
$session = System::getContainer()->get('session');
```


### Widget::addSubmit()

The `Widget::addSubmit()` method has been deprecated in Contao 4.0 and will be
removed in Contao 5.0. It already does not add a submit button anymore.


### Content elements

For reasons of backwards compatibility, it is currently not required to set the
`tl_content.ptable` column; it will treat an empty column like it had been set
to `tl_article`.

This behavior has been deprecated in Contao 4.0 and will no longer be supported
in Contao 5.0. If you have developed an extension which creates content
elements, make sure to always set the `ptable` column.


### Contao class loader

Even though we are still using the Contao class loader, it has been deprecated
in favor of the Composer class loader. You should no longer use it and you can
no longer use it to override arbitrary core classes.


### Using $this in configuration files

Using `$this` in configuration files such as `config/config.php` or `dca/*.php`
has been deprecated in Contao 4.0 and will no longer work in Contao 5.0.

You can use the static helper methods such as `System::loadLanguageFile()` or
`Controller::loadDataContainer()` instead.


### Constants

The constants `TL_ROOT`, `TL_MODE`, `TL_START`, `TL_SCRIPT` and `TL_REFERER_ID`
have been deprecated and will be removed in Contao 5.0.

Use the `kernel.root_dir` instead of `TL_ROOT`:

```php
$rootDir = dirname(System::getContainer()->getParameter('kernel.root_dir'));
```

Use the `ScopeAwareTrait` trait instead of using `TL_MODE`:

```php
use Contao\CoreBundle\Framework\ScopeAwareTrait;

class Test {
    use ScopeAwareTrait;

    public function isBackend() {
        return $this->isBackendScope();
    }

    public function isFrontend() {
        return $this->isFrontendScope();
    }
}
```

Use the kernel start time instead of `TL_START`:

```php
$startTime = System::getContainer()->get('kernel')->getStartTime();
```

Use the request stack to get the route instead of using `TL_SCRIPT`:

```php
$route = System::getContainer()->get('request_stack')->getCurrentRequest()->get('_route');

if ('contao_backend' === $route) {
    // Do something
}
```

Use the the request attribute `_contao_referer_id` instead of `TL_REFERER_ID`:

```php
$refererId = System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id');
```


### PHP entry points

Contao 4 only uses a single PHP entry point, namely the `app.php` or
`app_dev.php` file. The previous PHP entry points have been removed and a route
has been set up for each one instead (see UPGRADE.md).

Using the old paths is deprecated and will no longer work in Contao 5.0.


### ModuleLoader

The `ModuleLoader` class is no longer used and only kept for reasons of
backwards compatibility. It is deprecated and will be removed in Contao 5.0.
Use the container parameter `kernel.bundles` instead:

```php
$bundles = System::getContainer()->getParameter('kernel.bundles');
```


### database.sql files

Using `database.sql` files to set up tables is deprecated in Contao 4.0 and
will no longer be supported in Contao 5.0. Use DCA files instead:

```php
$GLOBALS['TL_DCA']['tl_example'] = array
(
	'config' => array
	(
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'name' => 'unique'
			)
		)
	),
	'fields' => array
	(
		'id' => array
		(
			'sql' => "int(10) unsigned NOT NULL auto_increment"
		),
		'name' => array
		(
			'sql' => "varchar(32) NULL"
		),
		'value' => array
		(
			'sql' => "varchar(32) NOT NULL default ''"
		)
	)
);

```
