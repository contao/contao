Deprecated features
===================

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


### Session::setData() and Session::getData()

The methods `Session::setData()` and `Session::getData()` have been deprecated
in Contao 4.0 and will be removed in Contao 5.0. Use the methods `replace()`
and `all()` instead.


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

Check the container scope instead of using `TL_MODE`:

```php
$isBackEnd  = System::getContainer()->isScopeActive(ContaoCoreBundle::SCOPE_BACKEND);
$isFrontEnd = System::getContainer()->isScopeActive(ContaoCoreBundle::SCOPE_FRONTEND);
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
