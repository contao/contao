# API changes

## Version 5.* to 6.0

### Input encoding

Contao 6 no longer filters or encodes user input automatically. This means you must make sure that all output is
properly encoded. The easiest way to do this is by using Twig templates.

If you send content to the browser without using Twig templates, use `StringUtil::specialchars()` or the
`contao.html_sanitizer` service to encode the output.

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
