# Changelog

This project adheres to [Semantic Versioning].

## [5.6.0-RC1] (2025-07-14)

**New features:**

- [#8094] Use a close icon instead of `×` or `&times;` ([m-vo])
- [#8434] Add "create new" buttons to the tree view ([aschempp])
- [#8519] Use security voters in the comments bundle ([aschempp])
- [#8242] Deprecate `Backend.enableImageSizeWidgets()` ([fritzmg])
- [#8011] Implement passkey support for the front end ([fritzmg])
- [#8511] Make the registration expiration time configurable ([zoglo])
- [#8012] Provide a template for an accessible navigation ([zoglo])
- [#8533] Refactor the "switch user" operations ([aschempp])
- [#8390] Add a calendar feed page controller ([fritzmg])
- [#8483] Always add a close button to messages ([aschempp])
- [#8523] Use a voter instead of a button callback for the alias element ([aschempp])
- [#8372] Simplify the operations menu labels ([aschempp])
- [#8066] Add the foundation for jobs ([Toflar])
- [#8529] Add a `hide()` method to the `DataContainerOperation` class ([Toflar])
- [#8522] Make the table dynamic in the `DisableAppConfiguredSettingsListener` ([Tastaturberuf])
- [#8437] Use POST requests for non-safe operations ([aschempp])
- [#8480] Use the jump target navigation in the "edit multiple" view ([zoglo])
- [#8204] Improve the "copy to clipboard" functionality ([m-vo])
- [#8346] Refactor the global operations ([aschempp])
- [#8479] Enable `postcss-preset-env` for the `flexible` theme ([zoglo])
- [#8495] Add a general `From` override for the `ContaoMailer` ([fritzmg])
- [#8418] Deprecate legacy content elements with fragment replacements ([fritzmg])
- [#8252] Add the "search indexer" page setting ([CMSworker])
- [#8462] Support ISO 3166-2 country subdivision codes ([ausi])
- [#8456] Implement rate limiting on the search indexer ([Toflar])
- [#8510] Update the `flexible` theme - reorder the `main.pcss` imports ([zoglo])
- [#8509] Update the `flexible` theme - move the responsive styles ([zoglo])
- [#8508] Update the `flexible` theme - move the highlight colors ([zoglo])
- [#8507] Update the `flexible` theme - cleanup the variables ([zoglo])
- [#8506] Update the `flexible` theme - clean up utilities and miscellaneous styles ([zoglo])
- [#8472] Add the Contao date formats to the Twig global ([fritzmg])
- [#8476] Add `HtmlAttributes` to `be_main` and meta tags to the `HtmlHeadBag` ([fritzmg])
- [#8473] Compress the serialized search document ([Toflar])
- [#8469] Restructure the `flexible` theme and rewrite it using PostCSS ([zoglo])
- [#8446] Rewrite the wizards to use Twig templates ([aschempp])
- [#8465] Allow selecting the backup within the `backup:restore` command ([zoglo])
- [#8400] Use SortableJS and move the drag handle to the left side of elements ([aschempp])
- [#8411] Add a separator to the DCA operations menu ([aschempp])
- [#8424] Make the subscribed services optional in the abstract controllers ([leofeyer])
- [#8404] Add the current member groups to the schema.org output ([Toflar])
- [#8410] Use listeners to set the dynamic parent table and the default labels ([aschempp])
- [#8393] Add a shortcut to get the `HtmlHeadBag` ([aschempp])
- [#8245] Implement a "store in session" setting for forms ([fritzmg])
- [#7825] Add a help text to form fields in the form generator ([de-es])
- [#8285] Extract the building of palletes and boxes into the data container ([aschempp])
- [#8395] Allow search indexers to index protected content ([Toflar])
- [#8370] Add a helper for the searchable content on the search document ([Toflar])
- [#8302] Add a palette helper to support working with the palette manipulator ([Toflar])
- [#8366] Update to SEAL 0.9 ([Toflar])
- [#8331] Deprecate the messenger priority interfaces in favor of the new `#[AsMessage]` attribute ([Toflar])
- [#8257] Rewrite the SERP widget to a Stimulus controller ([m-vo])
- [#8224] Do not require CSRF token checks on preflight requests ([Toflar])
- [#8212] Deprecate the `Contao\Messages` class ([fritzmg])
- [#8207] Rewrite the back end search to use Turbo streams ([m-vo])
- [#8226] Make `Backend.modalSelector()` work from within a modal dialog ([m-vo])
- [#6955] Support reloading DCAs ([ausi])
- [#8191] Deprecate the usage of the `typePrefix` property on models ([fritzmg])
- [#8064] Extract messages and the dialog element from the Template Studio ([m-vo])
- [#8072] Refactor the Turbo stream handling into a separate module ([m-vo])
- [#8184] Add the `Countable` interface to the `FilesystemItemIterator` ([m-vo])
- [#8052] Add a directory filter VFS decorator ([m-vo])
- [#8054] Add a `count()` helper method to the `FilesystemItemIterator` ([m-vo])
- [#8000] Add a "skip to content" link in the back end ([leofeyer])
- [#8010] Upgrade to PHPUnit 11 ([Toflar])
- [#8006] Add modern page layouts ([m-vo])
- [#8007] Add an "edit" action to the 2FA view ([bytehead])
- [#8002] Allow overwriting metadata via the `{{empty}}` insert tag ([ausi])
- [#7999] Add support for content elements in page layouts ([Toflar])
- [#7998] Enable format conversion for more image formats ([ausi])

**Fixed issues:**

- [#8550] Adjust the text indentation of the menu buttons ([m-vo])
- [#8549] Correctly hide disabled operations ([leofeyer])
- [#6859] Unlock `doctrine/dbal` 4.x and `doctrine/orm` 3.x ([fritzmg])
- [#8544] Only apply the button width in the `operations-menu` ([zoglo])
- [#8542] Fix the clickable area of the operation buttons ([aschempp])
- [#8537] Fix the operation menu position in the "paste into" view ([zoglo])
- [#8530] Distinguish between "copy" and "duplicate" ([leofeyer])
- [#8538] Use the default translations for the "new" button ([aschempp])
- [#8531] Consider the previous `as-grid` view for `DataContainer::MODE_PARENT` ([zoglo])
- [#8518] Simplify the positioning of the operations menu ([zoglo])
- [#8528] Handle empty `Content-Type` header in CORS ([aschempp])
- [#8517] Use `Backend::addToUrl()` when switching to "edit multiple" mode ([aschempp])
- [#8490] Switch the DCA request on `kernel.request` and `kernel.finish_request` ([ausi])
- [#8482] Fix the width of the select wizard ([zoglo])
- [#8478] Fix the DCA loading performance in dev mode ([ausi])
- [#8467] Fix the `width` within the `allowedAttributes` widget ([zoglo])
- [#8468] Fix a merge error in the guests migration ([aschempp])
- [#8459] Consider the `<hr>` element as a `menuLinkSelector` ([zoglo])
- [#8412] Do not use `MooTools.getElements()` in the `toggle-nodes-controller` ([zoglo])
- [#8398] Correctly close the `header_outlets` block ([Toflar])
- [#8296] Add the lost commit of #8207 again ([m-vo])
- [#8216] Copy the session in the DCA request switcher ([ausi])
- [#8178] Fix the module wizard and section wizard scripts ([m-vo])
- [#8071] Fix the "overwrite metadata fields" migration ([ausi])

[Semantic Versioning]: https://semver.org/spec/v2.0.0.html
[5.6.0-RC1]: https://github.com/contao/contao/releases/tag/5.6.0-RC1
[aschempp]: https://github.com/aschempp
[ausi]: https://github.com/ausi
[bytehead]: https://github.com/bytehead
[CMSworker]: https://github.com/CMSworker
[de-es]: https://github.com/de-es
[fritzmg]: https://github.com/fritzmg
[leofeyer]: https://github.com/leofeyer
[m-vo]: https://github.com/m-vo
[Tastaturberuf]: https://github.com/Tastaturberuf
[Toflar]: https://github.com/Toflar
[zoglo]: https://github.com/zoglo
[#6859]: https://github.com/contao/contao/pull/6859
[#6955]: https://github.com/contao/contao/pull/6955
[#7825]: https://github.com/contao/contao/pull/7825
[#7998]: https://github.com/contao/contao/pull/7998
[#7999]: https://github.com/contao/contao/pull/7999
[#8000]: https://github.com/contao/contao/pull/8000
[#8002]: https://github.com/contao/contao/pull/8002
[#8006]: https://github.com/contao/contao/pull/8006
[#8007]: https://github.com/contao/contao/pull/8007
[#8010]: https://github.com/contao/contao/pull/8010
[#8011]: https://github.com/contao/contao/pull/8011
[#8012]: https://github.com/contao/contao/pull/8012
[#8052]: https://github.com/contao/contao/pull/8052
[#8054]: https://github.com/contao/contao/pull/8054
[#8064]: https://github.com/contao/contao/pull/8064
[#8066]: https://github.com/contao/contao/pull/8066
[#8071]: https://github.com/contao/contao/pull/8071
[#8072]: https://github.com/contao/contao/pull/8072
[#8094]: https://github.com/contao/contao/pull/8094
[#8178]: https://github.com/contao/contao/pull/8178
[#8184]: https://github.com/contao/contao/pull/8184
[#8191]: https://github.com/contao/contao/pull/8191
[#8204]: https://github.com/contao/contao/pull/8204
[#8207]: https://github.com/contao/contao/pull/8207
[#8212]: https://github.com/contao/contao/pull/8212
[#8216]: https://github.com/contao/contao/pull/8216
[#8224]: https://github.com/contao/contao/pull/8224
[#8226]: https://github.com/contao/contao/pull/8226
[#8242]: https://github.com/contao/contao/pull/8242
[#8245]: https://github.com/contao/contao/pull/8245
[#8252]: https://github.com/contao/contao/pull/8252
[#8257]: https://github.com/contao/contao/pull/8257
[#8285]: https://github.com/contao/contao/pull/8285
[#8296]: https://github.com/contao/contao/pull/8296
[#8302]: https://github.com/contao/contao/pull/8302
[#8331]: https://github.com/contao/contao/pull/8331
[#8346]: https://github.com/contao/contao/pull/8346
[#8366]: https://github.com/contao/contao/pull/8366
[#8370]: https://github.com/contao/contao/pull/8370
[#8372]: https://github.com/contao/contao/pull/8372
[#8390]: https://github.com/contao/contao/pull/8390
[#8393]: https://github.com/contao/contao/pull/8393
[#8395]: https://github.com/contao/contao/pull/8395
[#8398]: https://github.com/contao/contao/pull/8398
[#8400]: https://github.com/contao/contao/pull/8400
[#8404]: https://github.com/contao/contao/pull/8404
[#8410]: https://github.com/contao/contao/pull/8410
[#8411]: https://github.com/contao/contao/pull/8411
[#8412]: https://github.com/contao/contao/pull/8412
[#8418]: https://github.com/contao/contao/pull/8418
[#8424]: https://github.com/contao/contao/pull/8424
[#8434]: https://github.com/contao/contao/pull/8434
[#8437]: https://github.com/contao/contao/pull/8437
[#8446]: https://github.com/contao/contao/pull/8446
[#8456]: https://github.com/contao/contao/pull/8456
[#8459]: https://github.com/contao/contao/pull/8459
[#8462]: https://github.com/contao/contao/pull/8462
[#8465]: https://github.com/contao/contao/pull/8465
[#8467]: https://github.com/contao/contao/pull/8467
[#8468]: https://github.com/contao/contao/pull/8468
[#8469]: https://github.com/contao/contao/pull/8469
[#8472]: https://github.com/contao/contao/pull/8472
[#8473]: https://github.com/contao/contao/pull/8473
[#8476]: https://github.com/contao/contao/pull/8476
[#8478]: https://github.com/contao/contao/pull/8478
[#8479]: https://github.com/contao/contao/pull/8479
[#8480]: https://github.com/contao/contao/pull/8480
[#8482]: https://github.com/contao/contao/pull/8482
[#8483]: https://github.com/contao/contao/pull/8483
[#8490]: https://github.com/contao/contao/pull/8490
[#8495]: https://github.com/contao/contao/pull/8495
[#8506]: https://github.com/contao/contao/pull/8506
[#8507]: https://github.com/contao/contao/pull/8507
[#8508]: https://github.com/contao/contao/pull/8508
[#8509]: https://github.com/contao/contao/pull/8509
[#8510]: https://github.com/contao/contao/pull/8510
[#8511]: https://github.com/contao/contao/pull/8511
[#8517]: https://github.com/contao/contao/pull/8517
[#8518]: https://github.com/contao/contao/pull/8518
[#8519]: https://github.com/contao/contao/pull/8519
[#8522]: https://github.com/contao/contao/pull/8522
[#8523]: https://github.com/contao/contao/pull/8523
[#8528]: https://github.com/contao/contao/pull/8528
[#8529]: https://github.com/contao/contao/pull/8529
[#8530]: https://github.com/contao/contao/pull/8530
[#8531]: https://github.com/contao/contao/pull/8531
[#8533]: https://github.com/contao/contao/pull/8533
[#8537]: https://github.com/contao/contao/pull/8537
[#8538]: https://github.com/contao/contao/pull/8538
[#8542]: https://github.com/contao/contao/pull/8542
[#8544]: https://github.com/contao/contao/pull/8544
[#8549]: https://github.com/contao/contao/pull/8549
[#8550]: https://github.com/contao/contao/pull/8550
