Known limitations
=================

Models and database connections
-------------------------------

The model registry currently only supports the main database connection. The
Contao framework supports opening additional database connections, however, you
cannot make models use them.

More information: https://github.com/contao/core/pull/6248


Moving content elements as non-admin user
-----------------------------------------

Non-admin users cannot copy or move content elements between different parent
types, e.g. from an article to a news item or from a news item to an event.
They can only copy or move elements from e.g. one article to another article.

More information: https://github.com/contao/core/issues/5234
