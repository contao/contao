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

Instead of `TL_ROOT` use:

```php
global $kernel;

$rootDir = dirname($kernel->getContainer()->getParameter('kernel.root_dir'));
```

Instead of `TL_MODE` to check for `BE` and `FE` use:

```php
global $kernel;

$isFE = $kernel->getContainer()->isScopeActive('frontend');
$isBE = $kernel->getContainer()->isScopeActive('backend');
```

Note: You can also extend the `ScopeAwareListener` in your own event listeners.

Instead of `TL_START` use:
```php
global $kernel;

$startTime = $kernel->getStartTime();
```

Note: This will only work if Symfony is in debug mode (`kernel.debug`).

Instead of `TL_SCRIPT` use the Symfony routing component to generate proper URLs.
To see the Contao routes, type `$ ./app/console router:debug` on your CLI.

If you used it to check where you currently are like that

```php
if (TL_SCRIPT == 'contao/main.php') {}
```

you should now go for something like

```php
global $kernel;

if ('contao_backend_main' === $kernel->getContainer()->get('request_stack')->getCurrentRequest()->get('_route')) {}
```


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

$bundles = $kernel->getContainer()->getParameter('kernel.bundles');
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
