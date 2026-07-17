# API changes

## Version 5.* to 6.0

### Input encoding

Contao 6 no longer encodes user input. Instead, values are stored in their raw form in the database. This means you
must ensure that all output is properly encoded. The easiest way to do this is by using Twig templates.

If you send content directly to the browser or return HTML from a hook, callback, or event listener without using Twig
templates, use `StringUtil::specialchars()` or `htmlspecialchars()` to encode it.

If user input contains HTML, use the `sanitize_html('contao')` Twig filter or the `contao.html_sanitizer` service to
sanitize it.

In Twig templates, make sure that anything you output using `|raw` does not contain untrusted data. If you are not
absolutely sure, replace `|raw` with `|sanitize_html('contao')`.

### HTML5 templates

Contao 6 no longer supports `.html5` templates. Use Twig templates instead.

### Double encoding enabled by default

Double encoding is now enabled by default in all core methods. If you need to escape a value without double encoding,
pass `false` as the third argument to `StringUtil::specialchars()`.

### tl_member.language no longer contains country codes

The `tl_member.language` field no longer contains country codes. If you need locale codes with regions, such as
`de_AT`, you can restore the previous behavior by using a custom callback:

```php
#AsCallback('tl_member', target: 'fields.language.options')
```

### decodeEntities and useRawRequestData removed

Because input encoding is no longer used, the `decodeEntities` and `useRawRequestData` options have been removed. All
values are now stored decoded, using the raw data from the request.

You can still enable HTML sanitization by setting `allowHtml` to `true`. For fields with `rte` set, HTML sanitization
is enabled automatically. You can disable it by setting `preserveTags` to `true`.

### child_record_callback removed

Use the `label_callback` instead.

### label_callback return type changed

String values returned by the `label_callback` are now HTML-encoded. If you need to return HTML, return a
`Contao\CoreBundle\DataContainer\RecordLabel` object with `htmlLabel` set.

### BBCode removed

BBCode support in the comments bundle has been removed. Existing comments are migrated to plain text format.

### Model property types and default values

Model values are now automatically cast to the correct type. An exception is thrown if a value cannot be converted.

```php
$contentModel->id = '123'; // Cast to integer
$contentModel->id = 'not_an_int'; // Throws an exception
```

Missing values in models now return their default value instead of `null`.

### Widget::generate() removed from frontend form widgets

Frontend form widgets no longer implement the `Widget::generate()` method. Use `Widget::parse()` instead.

### Backend themes

It is no longer possible to have multiple backend themes. Use the `contao.backend.custom_css` and
`contao.backend.custom_js` configuration options to customize the backend instead.

### File hashes

The database-assisted filesystem (DBAFS) now uses a 128-bit `xxHash` as its default hashing algorithm. Make sure the
database and filesystem are in sync before upgrading, for example by running `contao:filesync`. Existing `md5` hashes
will be removed from the database during a migration and rebuilt the first time the filesystem is synchronized.
