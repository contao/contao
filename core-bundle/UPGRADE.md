Contao API changes
==================

Version 3.* to 4.0
------------------

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
yet exist and all write operations such as `$file->write()` or `$file->append()`
were carried out directly on the target file.

This behavior could already be changed in Contao 3 by passing `true` as second
argument; the file was then only created if there was a write operation at all
and any operation was carried out on a temporary file, which was then moved to
its final destination.

In Contao 4, the changed behavior has become the default and the second argument
has been dropped.


### Protected folders

In Contao 3, the user files in the `files/` directory were publicly available
via HTTP by default and it was possible to protect certain subfolders. Now, due
to a technical change, the user files are protected by default and subfolders
have to be published explicitly to be available via HTTP.
