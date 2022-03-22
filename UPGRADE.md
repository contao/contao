# API changes

## Version 4.* to 5.0

### Removed {{post::*}} insert tag

The `{{post::*}}` insert tag has been removed. Accessing POST data is no longer possible with insert tags.
To access submitted form data on forward pages, use the new `{{form_session_data::*}}` insert tag instead.

### $_SESSION access no longer mapped to Symfony Session

The use of `$_SESSION` is discouraged because it makes testing and configuring alternative storage
back ends hard. In Contao 4, access to `$_SESSION` has been transparently mapped to the Symfony Session.
This has been removed. Use `$request->getSession()` directly instead.

### Simple Token Parser

Tokens which are not valid PHP variable names (e.g. `##0foobar##`) are not supported anymore by the
Simple Token Parser.

### $GLOBALS['TL_KEYWORDS']

Keyword support in articles, and as such also `$GLOBALS['TL_KEYWORDS']`, has been removed.

### Legacy routing

The legacy routing has been dropped. As such, the `getPageIdFromUrl` and `getRootPageFromUrl` hooks do
not exist anymore. Use Symfony routing instead.

### Custom entry points

The `initialize.php` file has been removed, so custom entry points will no longer work. Register your
entry points as controllers instead.

### ClassLoader

The `Contao\ClassLoader` has been removed. Use Composer autoloading instead.

### Encryption

The `Contao\Encryption` class and the `eval->encrypt` DCA flag have been removed. Use `save_callback`
and `load_callback` and libraries such as `phpseclib/phpseclib` instead.

### Internal CSS editor

The internal CSS editor has been removed. Export your existing CSS files, import them in the file manager
and then add them as external CSS files to the page layout.

### log_message()

The function `log_message()` has been removed. Use the Symfony logger services instead. Consequently, the
`Automator::rotateLogs()` method has been removed, too.

### config.dataContainer

The DCA `config.dataContainer` property needs to be a FQCN instead of just `Table` or `Folder`.

More information: https://github.com/contao/contao/pull/4322

### pageSelector and fileSelector widgets

The back end widgets `pageSelector` and `fileSelector` have been removed. Use the `picker` widget instead.

### Public folder

The public folder is now called `public` by default. It can be renamed in the `composer.json` file.
