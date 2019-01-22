# Known limitations

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

## Unique checks on encrypted data

The DCA option `'encrypt'=>true` cannot be used together with `'unique'=>true`,
because there is no effective way to check the unencrypted values for
duplicate entries.

More information: https://github.com/contao/core/issues/8144
