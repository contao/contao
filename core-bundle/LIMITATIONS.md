# Known limitations

## Models and database connections

The model registry currently only supports the main database connection. The
Contao framework supports opening additional database connections, however, you
cannot make models use them.

More information: https://github.com/contao/core/pull/6248

## Moving content elements as non-admin user

Non-admin users cannot copy or move content elements between different parent
types, e.g. from an article to a news item or from a news item to an event.
They can only copy or move elements from e.g. one article to another article.

More information: https://github.com/contao/core/issues/5234

## Copying multiple records as non-admin user

If an element can only be accessed after it was explicitly enabled in the user
settings (e.g. forms), non-admin users will not be able to copy multiple of
those elements at once. 

More information: https://github.com/contao/core-bundle/issues/1450
