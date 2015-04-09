Deprecated features
===================

### Using `$this` in configuration files

Using `$this` in configuration files such as `config/config.php` or `dca/*.php`
has been deprecated in Contao 4.0 and will no longer work in Contao 5.0.

You can use the static helper methods such as `System::loadLanguageFile()` or
`Controller::loadDataContainer()` instead.


### Constants

The constants `TL_ROOT`, `TL_MODE`, `TL_START` and `TL_SCRIPT` have been
deprecated and will be removed in Contao 5.0.

// FIXME: show the alternatives


### PHP entry points

Contao 4 only uses a single PHP entry point, namely the `app.php` or
`app_dev.php` file. The previous PHP entry points have been removed and a route
has been set up for each one instead.

Using the old paths is deprecated and will no longer work in Contao 5.0.


### `ModuleLoader`

The `ModuleLoader` class is no longer used and only kept for reasons of
backwards compatibility. It is deprecated and will be removed in Contao 5.0.
If you need to obtain a list of installed bundles, use the kernel instead:

```php
global $kernel;

$bundles = $kernel->getContainer()->get('kernel.bundles');
```


### `database.sql` files

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
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'name' => array
		(
			'sql'                     => "varchar(32) NULL"
		),
		'value' => array
		(
			'sql'                     => "varchar(32) NOT NULL default ''"
		)
	)
);

```
