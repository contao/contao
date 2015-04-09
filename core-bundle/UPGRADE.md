API changes
===========

Version 3.* to 4.0
------------------

### `dump()`

The `dump()` function has been replaced by the Symfony debug bundle. Its output
will be added to the web profiler.


### `tinymce.css` and `tiny_templates`

The style sheet `files/tinymce.css` and the folder `files/tiny_templates` have
been removed. If you want to use the feature, please adjust the TinyMCE config
file, which is now a template (e.g. `be_tinyMCE.html5`).


### `Frontend::parseMetaFile()`

The `Frontend::parseMetaFile()` method was deprecated since Contao 3 and has
been removed in Contao 4.0.


### `$_SESSION['TL_USER_LOGGED_IN']`

The `$_SESSION['TL_USER_LOGGED_IN']` flag has been removed.


### PHP entry points

Contao 4 only uses a single PHP entry point, namely the `app.php` or
`app_dev.php` file. The previous PHP entry points have been removed and a route
has been set up for each one instead.

 - `contao/confirm.php`  -> `contao_backend_confirm`
 - `contao/file.php`     -> `contao_backend_file`
 - `contao/help.php`     -> `contao_backend_help`
 - `contao/index.php`    -> `contao_backend_login`
 - `contao/install.php`  -> `contao_backend_install`
 - `contao/main.php`     -> `contao_backend`
 - `contao/page.php`     -> `contao_backend_page`
 - `contao/password.php` -> `contao_backend_password`
 - `contao/popup.php`    -> `contao_backend_popup`
 - `contao/preview.php`  -> `contao_backend_preview`
 - `contao/switch.php`   -> `contao_backend_switch`

The old paths are replaced automatically in the back end, still you should
adjust your templates to use `$this->route()` instead:

```php
// Old
<form action="contao/main.php">

// New
<form action="<?= $this->route('contao_backend') ?>">
```


### Disable aliases

In Contao 3, it was possible to disable aliases and make Contao use numeric IDs
only. This was a workaround for an old IIS server, which has now been dropped.

More information: https://github.com/contao/core-bundle/issues/118


### MySQL 5.5.3+

The minimum MySQL version has been raised to MySQL 5.5.3.


### `system/runonce.php`

The `system/runonce.php` file is no longer supported. If you need to set up a
`runonce.php` file, put it in the `src/Resources/contao/config/` directory.


### `DcaExtractor`

The `DcaExtractor` class is no longer instantiable via `new DcaExtractor()`.
Use the `DcaExtractor::getInstance($table)` method instead.


### MooTools slimbox

The MooTools "slimbox" plugin has been removed. Use the MooTools "mediabox" or
the jQuery "colorbox" plugin instead.


### `Message::generate()`

The `Message` class now supports scopes, which can optionally be passed as
second argument:

```php
// Add an error message to "my-scope"
Message::addError('An error ocurred', 'my-scope');

// Generate all messages in "my-scope"
Message::generate('my-scope');
```

The scope defaults to `TL_MODE`. The previous arguments of the `generate()`
method have been removed. If you want to output the messages without the
wrapping element, use `Message::generateUnwrapped()` instead.


### `ondelete_callback`

The `ondelete_callback` of the `DC_Table` driver now passes `$this` as last
argument just like in `DC_Folder` and in any other callback.


### Markup changes

The navigation menus and the search module are now using `<strong>` instead of
`<span>` to highlight the active menu item or keyword. The newsletter channel
menu is now using `<fieldset>` and `<legend>` instead of `<label>` and `<div>`.


### CSS class changes

The book navigation module now uses the CSS class `previous` instead of `prev`
for the link to the previous page. The pagination menu now uses the CSS class
`active` instead of `current` for the active menu item.

The classes "odd" and "even" are now correctly assigned to the table element.


### `new File()`

In Contao 3, `new File('tmp.txt')` automatically created the file if it did not
exist and all write operations such as `$file->write()` or `$file->append()`
were carried out directly on the target file.

This behavior could already be changed in Contao 3 by passing `true` as second
argument; the file was then only created if there was a write operation at all
and any operation was carried out on a temporary file first, which was then
moved to its final destination.

In Contao 4, this changed behavior has become the default and the second
argument has been dropped.


### Protected folders

In Contao 3, the user files in the `files/` directory were publicly available
via HTTP by default and it was possible to protect certain subfolders. Now, due
to a technical change, the user files are protected by default and subfolders
have to be published explicitly to be available via HTTP.
