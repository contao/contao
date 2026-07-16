# Deprecated features

## Input class

The `Input` class will no longer work in Contao 7. Use the request object instead.

## Hybrid, ContentElement, Module

The `Hybrid`, `ContentElement` and `Module` classes will be removed in Contao 7. Use a fragment controller instead.

## Service annotations

All of Contao's service annotations will be removed in Contao 7. Use PHP attributes instead, e.g. `#[AsCallback(…)]`
instead of `@Callback(…)` etc.

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
