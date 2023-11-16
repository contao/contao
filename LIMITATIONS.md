# Known limitations

## Cache blocks scheduled publishing

If elements are to be published time-controlled and the shared cache is active,
they will only be shown on the website after the cache has been purged.

More information: https://github.com/contao/contao/issues/3101

## Importing style sheets with a media query

Although the internal style sheet editor will add an existing media query when
exporting a style sheet, the media query will not be re-imported.

More information: https://github.com/contao/contao/issues/273

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

## Member login in preview if authenticated by a preview link

Logging in as a member discards the preview mode if the authentication happend
through a preview link. This makes it impossible to use preview links for
protected pages that require a member login.

More information: https://github.com/contao/contao/issues/6268
