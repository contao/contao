# API changes

## Version 5.* to 6.0

### Input encoding

User input is no longer filtered and encoded in Contao 6, which means that you have to ensure that all output is
properly encoded! The easiest way to do this is to use Twig templates.

If you send content to the browser without using Twig templates, make sure to use `StringUtil::specialchars()` or
the `contao.html_sanitizer` service to encode the output.

### HTML5 templates

Contao 6 no longer supports `.html5` templates. Use Twig templates instead.

### Double encoding enabled by default

Double encoding is now enabled in all core methods by default, you can pass `false` as the third parameter to
`StringUtil::specialchars()` if you need escaping without double encoding.

### tl_member.language no longer contains country codes

If you need locale codes with regions like `de_AT` you can restore the old behavior by using a custom callback like
`#AsCallback('tl_member', target: 'fields.language.options')`.

### Widget evaluation options decodeEntities and useRawRequestData got removed

Because input encoding is no longer used, the options `decodeEntities` and `useRawRequestData` got removed as all values
are now stored decoded using the raw data from the request. You can still enable HTML sanitization by setting
`allowHtml` to `true`. For fields with `rte` set, HTML sanitization is automatically enabled and can be disabled by
setting `preserveTags` to `true`.

### child_record_callback got removed

Use the `label_callback` instead.

### label_callback return type changed

String values returned by the `label_callback` get HTML encoded now. If you need to use HTML code you can return the
`Contao\CoreBundle\DataContainer\RecordLabel` object with `htmlLabel` set.

### BBCode got removed

Code for parsing BBCode and the BBCode option in the comments bundle got removed. Comments get migrated to plain text
format.

### Model property types and default values

Setting values in models get automatically cast to the correct type and throw an exception if conversion is not
possible. So setting `$contentModel->id = '123';` is cast to an integer and `$contentModel->id = 'not_an_int';` throws
an exception. Missing values in models now return its default value instead of `null`.

### Widget::generate() got removed from frontend form widgets

Frontend form widgets no longer implement the `Widget::generate()` method. Use `Widget::parse()` instead.
