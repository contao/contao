# API changes

## Version 4.* to 5.0

### Simple Token Parser

Tokens which are not valid PHP variable names (e.g. `##0foobar##`) are not supported anymore by the
Simple Token Parser.

### $GLOBALS['TL_KEYWORDS']

Keyword support in articles, and as such also `$GLOBALS['TL_KEYWORDS']`, has been removed.

### Legacy routing

The legacy routing has been dropped. As such, the `getPageIdFromUrl` and `getRootPageFromUrl` hooks do
not exist anymore. Use Symfony routing instead.

### initialize.php

The `initialize.php` support has been removed. Register your own Symfony routes to the routing instead.

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

### pageSelector and fileSelector widgets

The back end widgets `pageSelector` and `fileSelector` have been removed. Use the `picker` widget instead.

### Public folder

The public folder is now called `public` by default. It can be renamed in the `composer.json` file.
