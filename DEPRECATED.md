# Deprecated features

## Base tag

Relying on the `<base>` tag has been deprecated in Contao 5.0 and will no longer work in Contao 6. Use absolute paths
for links and assets instead.

## $GLOBALS['TL_LANGUAGE']

Using the global `$GLOBALS['TL_LANGUAGE']` has been deprecated in Contao 4.0 and
will no longer work in Contao 6. Use the locale from the request object instead:

```php
$locale = System::getContainer()->get('request_stack')->getCurrentRequest()->getLocale();
```
