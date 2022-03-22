# API changes

## Version 4.* to 5.0

### Dropped legacy Simple Token Parser

Tokens which are not valid PHP variable names (e.g. `##0foobar##`) are not supported anymore.

### Dropped $GLOBALS['TL_KEYWORDS']

Keyword support in articles, and as such also `$GLOBALS['TL_KEYWORDS']`, have been removed without
replacement.

### Dropped legacy routing

The legacy routing support has been dropped. As such, the `getPageIdFromUrl` and `getRootPageFromUrl`
hooks do not exist anymore. Use Symfony routing instead.

### Removed initialize.php

The `initialize.php` support has been removed without replacement. Register your own Symfony
Routes to the routing instead.

### Removed ClassLoader class

The `Contao\ClassLoader` has been removed without replacement. Use Composer autoloading instead.

### Removed Encryption class

The `Contao\Encryption` and the `eval->encrypt` DCA flag have been removed without replacement.
Use `save_callback` and `load_callback` and libraries such as `phpseclib/phpseclib`.

### Removed the internal CSS editor

The internal CSS editor has been removed without any replacement. If you still use it, make
sure to copy the generated CSS file to your assets.

### Removed log_message()

The function `log_message()` has been removed. Use the Symfony logger services instead.
Consequently, also `Automator::rotateLogs()` has been removed.

### pageSelector and fileSelector widgets

The back end widgets `pageSelector` and `fileSelector` have been removed. Use the `picker` widget instead.

### Public folder

The public folder is now called `public` by default. It can be renamed in the
`composer.json` file.
