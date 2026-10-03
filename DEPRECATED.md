# Deprecated features

## Input class

The `Input` class will no longer work in Contao 7. Use the request object instead.

## Hybrid, ContentElement, Module

The `Hybrid`, `ContentElement` and `Module` classes will be removed in Contao 7. Use a fragment controller instead.

## Service annotations

All of Contao's service annotations will be removed in Contao 7. Use PHP attributes instead, e.g. `#[AsCallback(…)]`
instead of `/** @Callback(…) */` etc.

## $GLOBALS['objPage']

Both `$GLOBALS['objPage']` and `global $objPage` will no longer work in Contao 7. Use the page finder service instead:

```php
$page = System::getContainer()->get('contao.routing.page_finder')->getCurrentPage();
```

## Base tag

Relying on the `<base>` tag will no longer work in Contao 7. Use absolute paths for links and assets instead.

## $GLOBALS['TL_LANGUAGE']

The global `$GLOBALS['TL_LANGUAGE']` variable will be removed in Contao 7. Use the request locale instead:

```php
$locale = System::getContainer()->get('request_stack')->getCurrentRequest()->getLocale();
```

## Twig filter insert_tag_raw

The Twig filter `|insert_tag_raw` will no longer work in Contao 7. Use `|insert_tag_html` instead.

## Twig filter input_encoded_to_plain_text

The Twig filter `|input_encoded_to_plain_text` and the corresponding `inputEncodedToPlainText()` methods of the
`StringRuntime` and `HtmlDecoder` classes will no longer work in Contao 7.

## Legacy document content APIs

The `response_context.end_of_head` Twig variable, the `end_of_head` Twig block and adding HTML head content through
`$GLOBALS['TL_CSS']`, `$GLOBALS['TL_JAVASCRIPT']`, `$GLOBALS['TL_STYLE_SHEETS']` or `$GLOBALS['TL_HEAD']`, as well as
the `response_context.end_of_body` Twig variable and adding end-of-body content through `$GLOBALS['TL_BODY']`, will
no longer work in Contao 7. Add `HtmlTag` instances to
the `HtmlHeadBag` or `HtmlBodyBag`, or use the
`{% add to head %}`, `{% add to stylesheets %}` and `{% add to body %}` Twig tags instead. Override the `head_tags` or
`end_of_body` Twig block to customize how the content is rendered.

The global-array fallback when using the Twig `add` tag without the corresponding response context bag will no longer
work in Contao 7.
