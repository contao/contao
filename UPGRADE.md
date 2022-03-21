# API changes

## Version 4.* to 5.0

### Removed log_message()

The function `log_message()` has been removed. Use the Symfony logger services instead.
Consequently, also `Automator::rotateLogs()` has been removed.

### DCA 'dataContainer' definition

The class name of the 'dataContainer' property needs to be a FQCN in 5.0 and above.

More information: https://github.com/contao/contao/pull/4322 and https://github.com/contao/docs/pull/1026

### pageSelector and fileSelector widgets

The back end widgets `pageSelector` and `fileSelector` have been removed. Use the `picker` widget instead.

### Public folder

The public folder is now called `public` by default. It can be renamed in the
`composer.json` file.
