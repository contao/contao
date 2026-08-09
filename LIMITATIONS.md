# Known limitations

## Moving content elements as non-admin user

Non-admin users cannot copy or move content elements between different parent types, e.g. from an article to a news
item or from a news item to an event. They can only copy or move elements from e.g. one article to another article.

More information: https://github.com/contao/core/issues/5234

## Infinitely repeating events in open-ended lists

Events that repeat indefinitely are shown only once in open-ended event lists, as they would otherwise create an
endless list.

More information: https://github.com/contao/contao/issues/8354
