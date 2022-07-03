# Known limitations

## Cache blocks scheduled publishing

If elements are to be published time-controlled and the shared cache is active, they will only be shown on the website
after the cache has been purged.

More information: https://github.com/contao/contao/issues/3101

## Moving content elements as non-admin user

Non-admin users cannot copy or move content elements between different parent types, e.g. from an article to a news
item or from a news item to an event. They can only copy or move elements from e.g. one article to another article.

More information: https://github.com/contao/core/issues/5234
