# Changelog

This project adheres to [Semantic Versioning].

## [5.6.9] (2025-12-15)

**Fixed issues:**

- [#9098] Fix headline in new box for certain elements ([aschempp])
- [#9126] Terminate parameters in the Altcha salt ([zoglo])
- [#9119] Fix using the deprecated `Template::attr()` method ([aschempp])
- [#9100] Wrap the accesible navigation script variables inside a block ([zoglo])
- [#9072] Improve the performance of the table data container provider ([Toflar])
- [#9102] Fix incorrect property naming in `FormAltcha` ([lukasbableck])
- [#9093] Fix the missing `disconnect()` in the `jobs-controller.js` ([Toflar])
- [#9088] Improve the `CalendarFeedListenerTest` ([fritzmg])
- [#9069] Allow confirming opt-in tokens as long as they are valid ([leofeyer])

## [5.6.8] (2025-12-04)

**Fixed issues:**

- [#9080] Fix accessing a method that potentially does not exist ([Toflar])
- [#9068] Correctly bypass the password change for impersonated users ([aschempp])

## [5.6.7] (2025-11-29)

**Fixed issues:**

- [#9050] Increase the field length for the ALTCHA challenge ([fritzmg])
- [#9048] Fix the `LayoutTemplateMigration` ([fritzmg])

## [5.6.6] (2025-11-26)

**Fixed issues:**

- [#9037] Allow overriding the back link if a DCA has a `ptable` ([aschempp])
- [#9044] Update the default content element palette in the maker bundle ([fritzmg])
- [#9043] Re-add the `flippedState` for the Choices dropdown ([zoglo])

## [5.6.5] (2025-11-25)

**Security fixes:**

- [CVE-2025-65960]: Remote code execution in template closures
- [CVE-2025-65961]: Cross-site scripting in templates

**Fixed issues:**

- [#8706] Use the attributes callback to initialize the layout template widget ([aschempp])
- [#9033] Output custom meta tags in Twig layouts ([fritzmg])
- [#9019] Check if operation description exists ([aschempp])
- [#9020] Fix `supportsSource` of `PageCommentsVoter` ([fritzmg])
- [#9025] Make class optional again for global operation with icon and button callback ([fritzmg])
- [#9027] Set widget error on onbeforesubmit exception ([aschempp])
- [#9004] Change the name of the default layout template in Twig layouts ([m-vo])
- [#9014] Improve the Template Studio cache invalidation ([m-vo])
- [#9001] Fix the picker offset in the list view ([zoglo])
- [#9009] Provide the index to SEAL when reindexing ([Toflar])
- [#8774] Add deferred execution for Twig layouts ([m-vo])
- [#9000] Rename the `is--hidden` class to `is-hidden` ([leofeyer])
- [#8988] Fix table sorting error in mutation observer ([aschempp])
- [#8782] Analyze if parent slots were overwritten in the Twig inspector ([m-vo])
- [#8989] Allow overriding the table header and footer blocks ([aschempp])
- [#8783] Render both content elements and front end modules in the Twig layout ([m-vo])
- [#8966] Increase the z-index of the jobs overlay ([stefansl])

## [5.6.4] (2025-10-31)

**Fixed issues:**

- [#8958] Change the variable name for the active mobile navigation color ([Werbeagentur-Kopfnuss])
- [#8956] Do not swallow DCA exceptions ([aschempp])
- [#8953] Add a viewport tag to the new default layout template ([leofeyer])
- [#8954] Define a minimum height for `.tl_content .inside` ([leofeyer])
- [#8952] Use `deprecation_info` instead of `deprecated_info` ([leofeyer])
- [#8951] Replace a leftover MooTools function call in the tooltips controller ([leofeyer])
- [#8905] Fix the protected icon in the content element preview ([aschempp])
- [#8944] Cast the slug to string when getting the theme path ([Tastaturberuf])
- [#8921] Allow disabling the back end search ([fritzmg])
- [#8919] Remove the `.operations > ul` padding caused by user-agent styles ([lukasbableck])
- [#8913] Prevent the mobile navigation from flashing on page load ([heimseiten])

## [5.6.3] (2025-09-30)

**Fixed issues:**

- [#8691] Use the locale switcher to set the user language ([fritzmg])
- [#8894] Do not double-encode the operation title ([aschempp])
- [#8758] Add more custom properties for the accessible navigation ([zoglo])
- [#8823] Improve the `DC_Folder` permission checks and use the operation builder ([aschempp])
- [#8773] Fix the legacy header buttons layout ([aschempp])
- [#8760] Make the accessible navigation breakpoint adjustable ([zoglo])
- [#8878] Show the titles in the fragment list view ([fritzmg])
- [#8892] Map the backup object to file names ([zoglo])
- [#8855] Display the form field label if the widget is missing ([fritzmg])
- [#8886] Fix the `datetime` types in the Doctrine entities ([fritzmg])
- [#8777] Harden against deleted templates when analyzing slots ([m-vo])
- [#8858] Fix the main headline styling in regular back end controllers ([fritzmg])
- [#8854] Use a pointer cursor for the preview toolbar toggle ([fritzmg])
- [#8866] Hide the content element preview if it's empty ([fritzmg])
- [#8841] Handle having no request in the scope matcher ([m-vo])
- [#8843] Make the "share preview link" operation primary ([Toflar])
- [#8824] Reduce the jobs polling interval to 5 seconds ([Toflar])
- [#8822] Fix a regression with the limit height toggle not being colored on hover ([zoglo])
- [#8820] Add operation list attributes to hide the limit height toggle by default ([aschempp])
- [#8812] Move the preview toolbar into the shadow DOM ([zoglo])
- [#8810] Preload the reader modules ([ausi])
- [#8768] Fix the Stimulus color picker preview ([zoglo])
- [#8788] Ensure no-op when the back end search is not available ([Toflar])
- [#8790] Prefetch the source editor links ([aschempp])
- [#8793] Use the media range query in the flexible theme ([zoglo])
- [#8784] Only set the request format when rendering a Turbo stream template ([m-vo])
- [#8775] Also apply cache headers in Twig layouts ([m-vo])

## [5.6.2] (2025-09-05)

**Fixed issues:**

- [#8722] Show the remove button for all file selections ([zoglo])
- [#8769] Adjust the widget grid alignment ([zoglo])
- [#8766] Make a job optional for the back end search reindex ([Toflar])
- [#8764] Fix the `var/logs` symlink under Symfony 7.3+ ([fritzmg])

## [5.6.1] (2025-08-28)

**Security fixes:**

- [CVE-2025-57758]: Improper access control in the back end voters
- [CVE-2025-57759]: Improper privilege management for page and article fields
- [CVE-2025-57757]: Information disclosure in the news module
- [CVE-2025-57756]: Information disclosure in the front end search index

**Fixed issues:**

- [#8747] Correctly handle non-array DCA operation labels ([aschempp])
- [#8743] Check if there are arguments before using `func_get_arg()` ([leofeyer])
- [#8736] Fix moving multiple items via the clipboard ([aschempp])
- [#8720] Do not create a response context if one already exists ([fritzmg])
- [#8667] Update the `input-map` CSS to match the previous `MooTools Sortables` ([zoglo])
- [#8710] Only apply the Turbo request cache to 200 OK responses ([Toflar])
- [#8707] Fix the search indexer migration when migrating from Contao 4.13 to 5.6 ([fritzmg])
- [#8715] Restore the previous `autoFocus` when navigating with Turbo Drive ([zoglo])
- [#8603] Fix the `getAllEvents` hook ([fritzmg])
- [#8700] A completed job must always be set to 100% progress ([Toflar])
- [#8689] Update to SEAL 0.12 ([Toflar])

## [5.6.0] (2025-08-18)

**Fixed issues:**

- [#8697] Rename the layout template ([aschempp])
- [#8696] Enable the `appendGroupInSearch` option for Choices ([zoglo])
- [#8695] Remove the debug markup from the Combiner ([ausi])
- [#8684] Show the context menu even if all operations are primary ([zoglo])
- [#8678] Adjust the `Cache-Control` for Turbo requests in the back end ([zoglo])
- [#8682] Fix the title tag in Twig layouts ([fritzmg])
- [#8666] Add the page class to the body element ([aschempp])
- [#8636] Fix the record fallback label ([aschempp])
- [#8664] Fix an undefined array key warning in the `AbstractLayoutPageController` ([zoglo])
- [#8663] Do not make the new Twig layouts the default ([leofeyer])

## [5.6.0-RC3] (2025-08-12)

**Fixed issues:**

- [#8513] Use `data-turbo-track="dynamic"` instead of reloading ([fritzmg])
- [#8634] Move the `tl_buttons` ID to the top element ([aschempp])
- [#8625] Use the operations builder for the versions view ([aschempp])
- [#8620] Fix the new buttons and allow to override the configuration ([aschempp])
- [#8653] Make the navigation burger color adjustable  ([zoglo])
- [#8623] Do not add a CSS class to the operation attributes ([aschempp])
- [#8626] Fall back to the label if an operation has no title ([aschempp])
- [#8643] Use `node` instead of `element` in the passkey mutation observer ([zoglo])
- [#8645] Output the global data for additional head/body content in layout pages ([m-vo])
- [#8578] Open the browser context menu on the second right-click again ([fritzmg])
- [#8621] Fix the drag handle of the image size items ([aschempp])
- [#8619] Always hide the parent node of invisible drag handles ([aschempp])
- [#8624] Optimize the theme import/export operations ([aschempp])
- [#8617] Do not add a leading white space in the `deeplink-controller` migration ([zoglo])
- [#8614] Remove `aria-hidden` on drag handles ([aschempp])
- [#8612] Remove a superfluous `"` in the `data-action` attribute of the `be_main` template ([lukasbableck])
- [#8570] Use a security voter to check the form field type access ([aschempp])

## [5.6.0-RC2] (2025-07-25)

**Fixed issues:**

- [#8586] Remove the close button from non-gallery `fileTree` widgets ([fritzmg])
- [#8583] Use the page finder in the fragment insert tag ([leofeyer])
- [#8594] Add the event dates to the calendar feed titles again ([fritzmg])
- [#8590] Deprecate `Contao\Feed` and `Contao\FeedItem` ([fritzmg])
- [#8593] Switch to `php-feed-io/feed-io` ([fritzmg])
- [#8566] Highlight the selected row in the single source picker ([de-es])
- [#8575] Show the drag handle in `MODE_PARENT` again ([fritzmg])
- [#8560] Properly reassign the active item in the accessible navigation ([zoglo])
- [#8576] Simplify the theme operations ([aschempp])
- [#8574] Fix a JS error in the operations menu controller after Turbo navigation ([aschempp])
- [#8573] Fix the separator spacing in the global context menu ([aschempp])
- [#8556] Fix the infinite loop on `encore dev --watch` ([zoglo])

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
[5.6.9]: https://github.com/contao/contao/releases/tag/5.6.9
[5.6.8]: https://github.com/contao/contao/releases/tag/5.6.8
[5.6.7]: https://github.com/contao/contao/releases/tag/5.6.7
[5.6.6]: https://github.com/contao/contao/releases/tag/5.6.6
[5.6.5]: https://github.com/contao/contao/releases/tag/5.6.5
[5.6.4]: https://github.com/contao/contao/releases/tag/5.6.4
[5.6.3]: https://github.com/contao/contao/releases/tag/5.6.3
[5.6.2]: https://github.com/contao/contao/releases/tag/5.6.2
[5.6.1]: https://github.com/contao/contao/releases/tag/5.6.1
[5.6.0]: https://github.com/contao/contao/releases/tag/5.6.0
[5.6.0-RC3]: https://github.com/contao/contao/releases/tag/5.6.0-RC3
[5.6.0-RC2]: https://github.com/contao/contao/releases/tag/5.6.0-RC2
[5.6.0-RC1]: https://github.com/contao/contao/releases/tag/5.6.0-RC1
[CVE-2025-65960]: https://github.com/contao/contao/security/advisories/GHSA-98vj-mm79-v77r
[CVE-2025-65961]: https://github.com/contao/contao/security/advisories/GHSA-68q5-78xp-cwwc
[CVE-2025-57758]: https://github.com/contao/contao/security/advisories/GHSA-7m47-r75r-cx8v
[CVE-2025-57759]: https://github.com/contao/contao/security/advisories/GHSA-qqfq-7cpp-hcqj
[CVE-2025-57757]: https://github.com/contao/contao/security/advisories/GHSA-w53m-gxvg-vx7p
[CVE-2025-57756]: https://github.com/contao/contao/security/advisories/GHSA-2xmj-8wmq-7475
[aschempp]: https://github.com/aschempp
[ausi]: https://github.com/ausi
[bytehead]: https://github.com/bytehead
[CMSworker]: https://github.com/CMSworker
[de-es]: https://github.com/de-es
[fritzmg]: https://github.com/fritzmg
[heimseiten]: https://github.com/heimseiten
[leofeyer]: https://github.com/leofeyer
[lukasbableck]: https://github.com/lukasbableck
[m-vo]: https://github.com/m-vo
[stefansl]: https://github.com/stefansl
[Tastaturberuf]: https://github.com/Tastaturberuf
[Toflar]: https://github.com/Toflar
[Werbeagentur-Kopfnuss]: https://github.com/Werbeagentur-Kopfnuss
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
[#8513]: https://github.com/contao/contao/pull/8513
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
[#8556]: https://github.com/contao/contao/pull/8556
[#8560]: https://github.com/contao/contao/pull/8560
[#8566]: https://github.com/contao/contao/pull/8566
[#8570]: https://github.com/contao/contao/pull/8570
[#8573]: https://github.com/contao/contao/pull/8573
[#8574]: https://github.com/contao/contao/pull/8574
[#8575]: https://github.com/contao/contao/pull/8575
[#8576]: https://github.com/contao/contao/pull/8576
[#8578]: https://github.com/contao/contao/pull/8578
[#8583]: https://github.com/contao/contao/pull/8583
[#8586]: https://github.com/contao/contao/pull/8586
[#8590]: https://github.com/contao/contao/pull/8590
[#8593]: https://github.com/contao/contao/pull/8593
[#8594]: https://github.com/contao/contao/pull/8594
[#8603]: https://github.com/contao/contao/pull/8603
[#8612]: https://github.com/contao/contao/pull/8612
[#8614]: https://github.com/contao/contao/pull/8614
[#8617]: https://github.com/contao/contao/pull/8617
[#8619]: https://github.com/contao/contao/pull/8619
[#8620]: https://github.com/contao/contao/pull/8620
[#8621]: https://github.com/contao/contao/pull/8621
[#8623]: https://github.com/contao/contao/pull/8623
[#8624]: https://github.com/contao/contao/pull/8624
[#8625]: https://github.com/contao/contao/pull/8625
[#8626]: https://github.com/contao/contao/pull/8626
[#8634]: https://github.com/contao/contao/pull/8634
[#8636]: https://github.com/contao/contao/pull/8636
[#8643]: https://github.com/contao/contao/pull/8643
[#8645]: https://github.com/contao/contao/pull/8645
[#8653]: https://github.com/contao/contao/pull/8653
[#8663]: https://github.com/contao/contao/pull/8663
[#8664]: https://github.com/contao/contao/pull/8664
[#8666]: https://github.com/contao/contao/pull/8666
[#8667]: https://github.com/contao/contao/pull/8667
[#8678]: https://github.com/contao/contao/pull/8678
[#8682]: https://github.com/contao/contao/pull/8682
[#8684]: https://github.com/contao/contao/pull/8684
[#8689]: https://github.com/contao/contao/pull/8689
[#8691]: https://github.com/contao/contao/pull/8691
[#8695]: https://github.com/contao/contao/pull/8695
[#8696]: https://github.com/contao/contao/pull/8696
[#8697]: https://github.com/contao/contao/pull/8697
[#8700]: https://github.com/contao/contao/pull/8700
[#8706]: https://github.com/contao/contao/pull/8706
[#8707]: https://github.com/contao/contao/pull/8707
[#8710]: https://github.com/contao/contao/pull/8710
[#8715]: https://github.com/contao/contao/pull/8715
[#8720]: https://github.com/contao/contao/pull/8720
[#8722]: https://github.com/contao/contao/pull/8722
[#8736]: https://github.com/contao/contao/pull/8736
[#8743]: https://github.com/contao/contao/pull/8743
[#8747]: https://github.com/contao/contao/pull/8747
[#8758]: https://github.com/contao/contao/pull/8758
[#8760]: https://github.com/contao/contao/pull/8760
[#8764]: https://github.com/contao/contao/pull/8764
[#8766]: https://github.com/contao/contao/pull/8766
[#8768]: https://github.com/contao/contao/pull/8768
[#8769]: https://github.com/contao/contao/pull/8769
[#8773]: https://github.com/contao/contao/pull/8773
[#8774]: https://github.com/contao/contao/pull/8774
[#8775]: https://github.com/contao/contao/pull/8775
[#8777]: https://github.com/contao/contao/pull/8777
[#8782]: https://github.com/contao/contao/pull/8782
[#8783]: https://github.com/contao/contao/pull/8783
[#8784]: https://github.com/contao/contao/pull/8784
[#8788]: https://github.com/contao/contao/pull/8788
[#8790]: https://github.com/contao/contao/pull/8790
[#8793]: https://github.com/contao/contao/pull/8793
[#8810]: https://github.com/contao/contao/pull/8810
[#8812]: https://github.com/contao/contao/pull/8812
[#8820]: https://github.com/contao/contao/pull/8820
[#8822]: https://github.com/contao/contao/pull/8822
[#8823]: https://github.com/contao/contao/pull/8823
[#8824]: https://github.com/contao/contao/pull/8824
[#8841]: https://github.com/contao/contao/pull/8841
[#8843]: https://github.com/contao/contao/pull/8843
[#8854]: https://github.com/contao/contao/pull/8854
[#8855]: https://github.com/contao/contao/pull/8855
[#8858]: https://github.com/contao/contao/pull/8858
[#8866]: https://github.com/contao/contao/pull/8866
[#8878]: https://github.com/contao/contao/pull/8878
[#8886]: https://github.com/contao/contao/pull/8886
[#8892]: https://github.com/contao/contao/pull/8892
[#8894]: https://github.com/contao/contao/pull/8894
[#8905]: https://github.com/contao/contao/pull/8905
[#8913]: https://github.com/contao/contao/pull/8913
[#8919]: https://github.com/contao/contao/pull/8919
[#8921]: https://github.com/contao/contao/pull/8921
[#8944]: https://github.com/contao/contao/pull/8944
[#8951]: https://github.com/contao/contao/pull/8951
[#8952]: https://github.com/contao/contao/pull/8952
[#8953]: https://github.com/contao/contao/pull/8953
[#8954]: https://github.com/contao/contao/pull/8954
[#8956]: https://github.com/contao/contao/pull/8956
[#8958]: https://github.com/contao/contao/pull/8958
[#8966]: https://github.com/contao/contao/pull/8966
[#8988]: https://github.com/contao/contao/pull/8988
[#8989]: https://github.com/contao/contao/pull/8989
[#9000]: https://github.com/contao/contao/pull/9000
[#9001]: https://github.com/contao/contao/pull/9001
[#9004]: https://github.com/contao/contao/pull/9004
[#9009]: https://github.com/contao/contao/pull/9009
[#9014]: https://github.com/contao/contao/pull/9014
[#9019]: https://github.com/contao/contao/pull/9019
[#9020]: https://github.com/contao/contao/pull/9020
[#9025]: https://github.com/contao/contao/pull/9025
[#9027]: https://github.com/contao/contao/pull/9027
[#9033]: https://github.com/contao/contao/pull/9033
[#9037]: https://github.com/contao/contao/pull/9037
[#9043]: https://github.com/contao/contao/pull/9043
[#9044]: https://github.com/contao/contao/pull/9044
[#9048]: https://github.com/contao/contao/pull/9048
[#9050]: https://github.com/contao/contao/pull/9050
[#9068]: https://github.com/contao/contao/pull/9068
[#9069]: https://github.com/contao/contao/pull/9069
[#9072]: https://github.com/contao/contao/pull/9072
[#9080]: https://github.com/contao/contao/pull/9080
[#9088]: https://github.com/contao/contao/pull/9088
[#9093]: https://github.com/contao/contao/pull/9093
[#9098]: https://github.com/contao/contao/pull/9098
[#9100]: https://github.com/contao/contao/pull/9100
[#9102]: https://github.com/contao/contao/pull/9102
[#9119]: https://github.com/contao/contao/pull/9119
[#9126]: https://github.com/contao/contao/pull/9126
