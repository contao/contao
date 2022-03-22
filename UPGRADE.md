# API changes

## Version 4.* to 5.0

### CSS classes "first", "last", "even" and "odd"

The CSS classes `first`, `last`, `even`, `odd`, `row_*` and `col_*` are no longer applied anywhere.
Use CSS selectors instead.

### Template changes

The items in the `ce_list` template no longer consist of an associative array
containing the list item's CSS class and content. Instead it will only be the content.

```php
<!-- OLD -->
<?php foreach ($this->items as $item): ?>
  <li<?php if ($item['class']): ?> class="<?= $item['class'] ?>"<?php endif; ?>><?= $item['content'] ?></li>
<?php endforeach; ?>

<!-- NEW -->
<?php foreach ($this->items as $item): ?>
  <li><?= $item ?></li>
<?php endforeach; ?>
```

## Version 4.* to 4.11

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
