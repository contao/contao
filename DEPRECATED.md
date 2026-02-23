# Deprecated features

## Service annotations

All of Contao's service annotations have been deprecated in Contao 5.4 and will no longer work in Contao 6. Use the
respective PHP attributes instead, e.g. `#[AsCallback(…)]` instead of `@Callback(…)` etc.

## $GLOBALS['objPage']

Both `$GLOBALS['objPage']` and `global $objPage` have been deprecated in Contao 5.4 and will no longer work in Contao 6.
Use the page finder service instead:

```php
$page = System::getContainer()->get('contao.routing.page_finder')->getCurrentPage();
```

## Base tag

Relying on the `<base>` tag is deprecated in Contao 5.0 and will no longer work in Contao 6. Use absolute paths
for links and assets instead.

## $GLOBALS['TL_LANGUAGE']

Using the global `$GLOBALS['TL_LANGUAGE']` is deprecated in Contao 4.0 and will no longer work in Contao 6. Use
the locale from the request object instead:

```php
$locale = System::getContainer()->get('request_stack')->getCurrentRequest()->getLocale();
```
