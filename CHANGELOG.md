# Changelog

This project adheres to [Semantic Versioning].

## [5.3.6] (2024-04-17)

**Fixed issues:**

- [#7122] Ensure compatibility with Twig 3.9 ([leofeyer])
- [#7112] Handle empty strings in the `StringResolver` class ([qzminski])

## [5.3.5] (2024-04-16)

**Fixed issues:**

- [#7113] Fix the order of the media block in the text element markup ([ausi])
- [#7107] Use Encore to minify the SVG icons ([leofeyer])
- [#7071] Add the missing styles to the new table element ([zoglo])
- [#7106] Enable the `sortAttrs` option in the SVGO configuration ([leofeyer])
- [#7017] Fix the elements check in the `modulewizard.js` script ([qzminski])
- [#7073] Use `display: grid` in the image gallery preview ([zoglo])
- [#7074] Initialize Handorgel on the element ([zoglo])
- [#7081] Add the missing `WysiwygStyleProcessor ` autowiring alias ([Toflar])
- [#7064] Also unset the `disable`, `start` and `stop` fields when an admin edits themselves ([aschempp])
- [#7057] Cache SQL queries in the page type voter ([aschempp])
- [#7046] Fix some edge cases when parsing HTML style attributes ([ausi])

## [5.3.4] (2024-04-09)

**Security fixes:**

- [CVE-2024-28235]: Session cookie disclosure in the crawler
- [CVE-2024-28190]: Cross site scripting in the file manager
- [CVE-2024-28191]: Insert tag injection via the form generator
- [CVE-2024-28234]: Insufficient BBCode sanitization

## [5.3.3] (2024-03-22)

**Fixed issues:**

- [#7045] Fix a bug in `setIfExists()` with Stringable objects ([ausi])
- [#7044] Fix double encoding/decoding in the `HtmlAttributes` class ([ausi])

## [5.3.2] (2024-03-21)

**New features:**

- [#7037] Add the `csp_unsafe_inline_style` Twig filter ([ausi])

**Fixed issues:**

- [#7039] Revert the changes to the "file uploaded" check ([fritzmg])
- [#7032] Harden mime type handling in the `FilesystemItem` class ([m-vo])
- [#7026] Show headlines in article teasers again ([zoglo])
- [#7006] Use the fragment registry in the `debug:fragments` command ([bytehead])
- [#7031] Allow version 5 of lcobucci/jwt ([leofeyer])
- [#7027] Register theme templates in the global namespace, too ([ausi])
- [#7028] Enable collapsible fieldsets without storage ([aschempp])
- [#7021] Override the access decision strategy instead of the manager ([aschempp])
- [#7016] Fix a PHP 8 warning in the `tl_article.getActiveLayoutSections()` method ([qzminski])
- [#7008] Fix the traceable access decision manager ([aschempp])
- [#7007] Return to the list view after adding items to the clipboard ([aschempp])
- [#6996] Use voters for theme permissions ([aschempp])
- [#7002] Add the user access voter ([aschempp])
- [#6993] Fix the front end module permissions ([aschempp])
- [#7005] Make the `ParentAccessTrait::hasAccessToParent()` method private ([aschempp])
- [#7003] Improve permission error message for DCA actions ([aschempp])
- [#6968] Set the email message priority to "high" ([Toflar])
- [#6995] Disable background workers if they are not supported ([Toflar])
- [#6952] Convert protocol-relative URLs in the string resolver ([aschempp])

## [5.3.1] (2024-03-08)

**New features:**

- [#6954] Register the `dotenv:dump` command by default in the Contao managed edition ([Toflar])

**Fixed issues:**

- [#6982] Cache `Image::getHtml()` to speed up the tree view ([Toflar])
- [#6963] Fix the newsfeed migration ([aschempp])
- [#6916] Use `Model::findById()` instead of `Model::findByPk()` ([leofeyer])
- [#6960] Show the route configuration in the news feed page ([aschempp])
- [#6969] Fix the `dotenv:dump` command ([aschempp])
- [#6979] Allow using insert tags in image `alt` and `title` attributes ([leofeyer])
- [#6975] Deprecate inheriting CSS classes in nested elements ([aschempp])
- [#6978] Use `UrlUtil::makeAbsolute()` when converting relative URLs ([leofeyer])
- [#6961] Fix a type error in the login module ([aschempp])
- [#6956] Use `attrs().mergeWith()` in Twig templates ([leofeyer])
- [#6962] Make sure the `.env.local.php` is loaded correctly ([Toflar])
- [#6953] Fix double inheritance of legacy templates in Twig ([ausi])
- [#6950] Correctly register the `AutoRefreshTemplateHierarchyListener` ([m-vo])
- [#6951] Fix that the guests migration only migrates one field at a time ([aschempp])
- [#6943] Correctly generate the URLs to subscribe to comments ([leofeyer])
- [#6946] Improve the performance of the database dumper ([Toflar])
- [#6944] Correctly check if a "jump to" page is set when generating event feeds ([leofeyer])
- [#6919] Make full authentication optional in the personal data module ([leofeyer])
- [#6941] Handle unicode strings in insert tag flags ([ausi])
- [#6938] Add a button to the "invalid request token" template ([leofeyer])
- [#6939] Correctly implement the `ImageFactoryInterface` ([leofeyer])
- [#6936] Fix the Twig loader infrastructure ([m-vo])
- [#6927] Use files instead of `data:` resources to avoid breaking CSP ([leofeyer])
- [#6925] Only make string URL absolute if it does not have a scheme ([aschempp])
- [#6917] Fix two CSS issues ([leofeyer])

## [5.3.0] (2024-02-16)

**Fixed issues:**

- [#6854] Handle routing exceptions during news and event URL generation ([fritzmg])
- [#6900] Improve logging of request parameters ([aschempp])
- [#6898] Add `type="button"` to the accordion toggler ([fritzmg])
- [#6895] Fix the column name in the "remember me" migration ([aschempp])
- [#6893] Move adding the schema.org data to the `_download.html.twig` component ([leofeyer])
- [#6889] Correctly cache Contao translations that only exist as Symfony translations ([fritzmg])
- [#6890] Always allow the "read" action in the front end modules voter ([bezin])
- [#6880] Correctly handle dark icons in `data-icon` and `data-icon-disabled` ([zoglo])

## [5.3.0-RC4] (2024-02-12)

**New features:**

- [#6814] Allow adding a source to multiple CSP directives at once ([aschempp])
- [#6858] Remove the `@internal` flag from the backup manager ([Toflar])

**Fixed issues:**

- [#6882] Make the commands lazy again ([leofeyer])
- [#6852] Fix the `TemplateOptionsListener` ([fritzmg])
- [#6867] Correctly initialize multiple accordions on the same page ([leofeyer])
- [#6861] Hide the trail in the SERP preview if no URL can be generated ([leofeyer])
- [#6856] Add the "toggle visibility" button for articles and content elements again ([aschempp])
- [#6857] Fix the "remember me" migration ([leofeyer])
- [#6855] Cast the template identifier to string ([leofeyer])

## [5.3.0-RC3] (2024-02-05)

**New features:**

- [#6819] Focus the first input/textarea after duplicating a wizard row ([leofeyer])
- [#6436] Add a global Twig variable with Contao state ([aschempp])
- [#6742] Add a basic entity for zero-width whitespaces ([aschempp])

**Fixed issues:**

- [#6851] Rewrite `Controller::getParentEntries()` ([ausi])
- [#6833] Handle dynamic parent tables in the `Controller::getParentEntries()` method ([leofeyer])
- [#6843] Fix relative front end preview links ([aschempp])
- [#6840] Keep login module errors ([aschempp])
- [#6838] Fix the article content voter ([aschempp])
- [#6841] Remove obsolete hardcoded configuration in the page registry ([aschempp])
- [#6835] Do not require full authentication in the "change password" module ([leofeyer])
- [#6803] Fix the referrer URL if elements are moved inside a nested element ([leofeyer])
- [#6839] Fix routes with parameters in the SERP widget ([aschempp])
- [#6831] Correctly set the target path in the login module ([leofeyer])
- [#6830] Fix the order of the content elements ([aschempp])
- [#6805] Correctly handle denied access in the firewall ([aschempp])
- [#6815] Drop the custom "remember me" implementation ([aschempp])
- [#6807] Improve the debug message for `FigureBuilder` link attributes ([aschempp])
- [#6809] Mark `$secret` as sensitive parameter ([aschempp])
- [#6794] Fix ptable for copyAll and cutAll ([ausi])

## [5.3.0-RC2] (2024-01-26)

**New features:**

- [#6738] Add a Twig function to generate content URLs ([aschempp])
- [#6719] Support CSP on WYSIWYG editors like TinyMCE ([Toflar])

**Fixed issues:**

- [#6788] Use the content URL generator in the redirect page controller ([aschempp])
- [#6775] Remove the `@internal` flag from the HTTP cache subscribers ([leofeyer])
- [#6758] Improve how headlines can be adjusted in Twig ([m-vo])
- [#6747] Increase the `z-index` of the jump targets ([zoglo])
- [#6767] Use the `inputUnit` widget for the section headline field ([leofeyer])
- [#6743] Use autoconfiguration where possible ([leofeyer])
- [#6761] Limit the CSP header size to avoid server errors ([Toflar])
- [#6760] Correctly set the link title and text in the downloads controller ([fritzmg])
- [#6759] Normalize the Twig CSP method names ([fritzmg])
- [#6744] Fix the "delete files" button in the file manager ([aschempp])
- [#6740] Add the `TemplateTrait::inlineStyle()` method ([fritzmg])
- [#6737] Properly assign parameters to `contao.crawl.escargot.factory` ([zoglo])
- [#6736] Unify the deprecation messages ([leofeyer])

## [5.3.0-RC1] (2024-01-18)

**New features:**

- [#6606] Generate newsletter URLs using the content URL generator ([aschempp])
- [#6597] Generate FAQ URLs using the content URL generator ([aschempp])
- [#6604] Generate news URLs using the content URL generator ([aschempp])
- [#6607] Generate event URLs using the content URL generator ([aschempp])
- [#6596] Implement the content URL generator ([aschempp])
- [#6631] Add the ability to set Content Security Policies ([fritzmg])
- [#6672] Add a Stimulus controller to handle scrolling in the back end ([zoglo])
- [#6392] Implement the redirect page as page controller ([fritzmg])
- [#5424] Add a description list content element ([aschempp])
- [#6215] Add canonical links to news and events ([aschempp])
- [#6675] Add the page permission voters ([aschempp])
- [#6232] Implement front end module permissions ([bezin])
- [#6646] Add an image size voter ([aschempp])
- [#6584] Add enum support for DCAs and models ([SeverinGloeckle])
- [#6683] Add more database indexes ([Toflar])
- [#6650] Decouple the calendar, FAQ and news bundles from the comments bundle ([zoglo])
- [#6639] Allow adding a "lost password" page to the login module ([zoglo])
- [#6529] Add the DNS mapping migration ([fritzmg])
- [#5810] Add a VFS decorator that supports user permissions ([m-vo])
- [#6605] Optimize the MySQL indexes ([leofeyer])
- [#6652] Sort options by key if they use language references ([leofeyer])
- [#6558] Inline the CSS from a newsletter template before sending ([leofeyer])
- [#6626] Add a modern content slider element ([leofeyer])
- [#6673] Properly name the worker supervision cron ([Toflar])
- [#6669] Use the `attributes_callback` to make the logout redirect mandatory ([aschempp])
- [#6661] Add a z-index to the limit toggler ([zoglo])
- [#6668] Sync the logic to generate multiple aliases ([aschempp])
- [#6516] Implement worker supervision ([Toflar])
- [#6651] Do not load style sheets lazily by default ([leofeyer])
- [#6648] Add a modern accordion element ([leofeyer])
- [#6615] Automatic login for cross-domain preview links ([aschempp])
- [#6643] Add a voter for tl_newsletter_recipients ([aschempp])
- [#6642] Add a voter for tl_undo ([aschempp])
- [#6638] Add the onpalette_callback ([aschempp])
- [#6553] Automatically enable the Strict Transport Security (HSTS) header ([Toflar])
- [#6620] Rename "childs" to "children" ([leofeyer])
- [#6521] Nested content elements ([ausi])
- [#6469] Add more security voters ([leofeyer])
- [#6614] Sort the tables in the database backup ([de-es])
- [#6603] Unify the deprecation messages ([leofeyer])
- [#6594] Remove column from articles URL ([aschempp])
- [#6353] Add a tab menu to jump to palette sections ([leofeyer])
- [#6583] Make Symfony 6.4 the minimum version ([leofeyer])
- [#6569] Show the back end header on scroll-up ([leofeyer])
- [#6557] Make the back end header sticky on all devices ([leofeyer])
- [#6551] Use the picker to select article target in news and calendar ([aschempp])
- [#6518] Populate `contao_` Symfony translations into `$GLOBALS['TL_LANG']` ([fritzmg])
- [#6527] Rewrite tree mode toggling to Stimulus controller ([aschempp])
- [#6303] Implement a global "expand/collapse elements" button ([aschempp])
- [#6533] Register a web processor to add log extras ([aschempp])
- [#6528] Automatically generate the global operations ([aschempp])
- [#6206] Make the downloads controller more flexible for own sources ([Toflar])
- [#6494] Automatically translate the default maintenance template ([Toflar])
- [#6515] Log failed messages in the back end ([Toflar])
- [#6485] Add schema.org support to the virtual file system ([Toflar])
- [#6513] Automatically load routes in app controllers ([aschempp])
- [#6465] Allow to re-use the ProcessUtil data ([Toflar])
- [#6496] Add the event end date to the schema.org data ([leofeyer])
- [#6506] Add a maximum duration for the back end crawler ([leofeyer])
- [#6495] Make the back end crawler configurable ([leofeyer])
- [#6497] Wrap the news date and author in a template block ([leofeyer])
- [#6498] Replace insert tag flags based on the context ([leofeyer])
- [#6477] Clean up a TODO ([Toflar])
- [#6429] Deprecate the `MergeHttpHeadersListener` class ([leofeyer])
- [#6446] Rename the `templates/_new` folder to `templates/twig` ([leofeyer])
- [#6404] Remove the BC layers in the `ContaoCache` class ([fritzmg])
- [#6386] Deprecate the `System::setCookie()` method ([Toflar])
- [#6236] Allow array for page parameters ([aschempp])
- [#6337] Upgrade the Symfony contracts ([leofeyer])
- [#6338] Remove the "roave/better-reflection" dependency ([leofeyer])
- [#6336] Make doctrine/dbal 3.6 the minimum version ([leofeyer])
- [#6339] Upgrade doctrine/collections and doctrine/persistence ([leofeyer])
- [#6335] Make Symfony 6.3 the minimum version ([leofeyer])
- [#6289] Set auto password hasher for all user classes ([fritzmg])
- [#6324] Always set the `JSON_THROW_ON_ERROR` flag ([leofeyer])
- [#6157] Use createElementNS for namespaced XML elements ([ausi])

**Fixed issues:**

- [#6723] Introduce `TemplateTrait` to fix missing method in `Widget` ([fritzmg])
- [#6718] Fix edit-all operation if records can only be deleted ([aschempp])
- [#6714] Fix the missing icon for DCA operations again ([aschempp])
- [#6708] Remove the `contao.downloadable_files` parameter ([leofeyer])
- [#6707] Correctly set the ptable for copy and cut actions ([ausi])
- [#6676] Use the `_attributes` suffix in the accordion template ([leofeyer])
- [#6670] Fetch visible root trail record from database ([aschempp])
- [#6665] Only check the first record to be restored ([aschempp])
- [#6645] Move ptable logic from tl_content to DC_Table ([ausi])
- [#6641] Fix missing `ptabe` for `saveNcreate` and `saveNduplicate` ([ausi])
- [#6636] Vote on the current token in the voters ([aschempp])
- [#6628] Fix DCA voters not checking module and parent update access ([aschempp])
- [#6627] Fix favorites voter not voting on current record ([aschempp])
- [#6595] Deprecate the `PageModel::getPreviewUrl()` method ([aschempp])
- [#6600] Check for parameter existence ([Toflar])
- [#6590] Move the `ModelMetadataTrait` to the correct namespace ([leofeyer])
- [#6598] Do not smooth-scroll on devices with reduced motion ([aschempp])
- [#6530] Also remove global operations in bundles ([aschempp])

[Semantic Versioning]: https://semver.org/spec/v2.0.0.html
[5.3.6]: https://github.com/contao/contao/releases/tag/5.3.6
[5.3.5]: https://github.com/contao/contao/releases/tag/5.3.5
[5.3.4]: https://github.com/contao/contao/releases/tag/5.3.4
[5.3.3]: https://github.com/contao/contao/releases/tag/5.3.3
[5.3.2]: https://github.com/contao/contao/releases/tag/5.3.2
[5.3.1]: https://github.com/contao/contao/releases/tag/5.3.1
[5.3.0]: https://github.com/contao/contao/releases/tag/5.3.0
[5.3.0-RC4]: https://github.com/contao/contao/releases/tag/5.3.0-RC4
[5.3.0-RC3]: https://github.com/contao/contao/releases/tag/5.3.0-RC3
[5.3.0-RC2]: https://github.com/contao/contao/releases/tag/5.3.0-RC2
[5.3.0-RC1]: https://github.com/contao/contao/releases/tag/5.3.0-RC1
[CVE-2024-28235]: https://github.com/contao/contao/security/advisories/GHSA-9jh5-qf84-x6pr
[CVE-2024-28190]: https://github.com/contao/contao/security/advisories/GHSA-v24p-7p4j-qvvf
[CVE-2024-28191]: https://github.com/contao/contao/security/advisories/GHSA-747v-52c4-8vj8
[CVE-2024-28234]: https://github.com/contao/contao/security/advisories/GHSA-j55w-hjpj-825g
[aschempp]: https://github.com/aschempp
[ausi]: https://github.com/ausi
[bezin]: https://github.com/bezin
[bytehead]: https://github.com/bytehead
[de-es]: https://github.com/de-es
[fritzmg]: https://github.com/fritzmg
[leofeyer]: https://github.com/leofeyer
[m-vo]: https://github.com/m-vo
[qzminski]: https://github.com/qzminski
[SeverinGloeckle]: https://github.com/SeverinGloeckle
[Toflar]: https://github.com/Toflar
[zoglo]: https://github.com/zoglo
[#5424]: https://github.com/contao/contao/pull/5424
[#5810]: https://github.com/contao/contao/pull/5810
[#6157]: https://github.com/contao/contao/pull/6157
[#6206]: https://github.com/contao/contao/pull/6206
[#6215]: https://github.com/contao/contao/pull/6215
[#6232]: https://github.com/contao/contao/pull/6232
[#6236]: https://github.com/contao/contao/pull/6236
[#6289]: https://github.com/contao/contao/pull/6289
[#6303]: https://github.com/contao/contao/pull/6303
[#6324]: https://github.com/contao/contao/pull/6324
[#6335]: https://github.com/contao/contao/pull/6335
[#6336]: https://github.com/contao/contao/pull/6336
[#6337]: https://github.com/contao/contao/pull/6337
[#6338]: https://github.com/contao/contao/pull/6338
[#6339]: https://github.com/contao/contao/pull/6339
[#6353]: https://github.com/contao/contao/pull/6353
[#6386]: https://github.com/contao/contao/pull/6386
[#6392]: https://github.com/contao/contao/pull/6392
[#6404]: https://github.com/contao/contao/pull/6404
[#6429]: https://github.com/contao/contao/pull/6429
[#6436]: https://github.com/contao/contao/pull/6436
[#6446]: https://github.com/contao/contao/pull/6446
[#6465]: https://github.com/contao/contao/pull/6465
[#6469]: https://github.com/contao/contao/pull/6469
[#6477]: https://github.com/contao/contao/pull/6477
[#6485]: https://github.com/contao/contao/pull/6485
[#6494]: https://github.com/contao/contao/pull/6494
[#6495]: https://github.com/contao/contao/pull/6495
[#6496]: https://github.com/contao/contao/pull/6496
[#6497]: https://github.com/contao/contao/pull/6497
[#6498]: https://github.com/contao/contao/pull/6498
[#6506]: https://github.com/contao/contao/pull/6506
[#6513]: https://github.com/contao/contao/pull/6513
[#6515]: https://github.com/contao/contao/pull/6515
[#6516]: https://github.com/contao/contao/pull/6516
[#6518]: https://github.com/contao/contao/pull/6518
[#6521]: https://github.com/contao/contao/pull/6521
[#6527]: https://github.com/contao/contao/pull/6527
[#6528]: https://github.com/contao/contao/pull/6528
[#6529]: https://github.com/contao/contao/pull/6529
[#6530]: https://github.com/contao/contao/pull/6530
[#6533]: https://github.com/contao/contao/pull/6533
[#6551]: https://github.com/contao/contao/pull/6551
[#6553]: https://github.com/contao/contao/pull/6553
[#6557]: https://github.com/contao/contao/pull/6557
[#6558]: https://github.com/contao/contao/pull/6558
[#6569]: https://github.com/contao/contao/pull/6569
[#6583]: https://github.com/contao/contao/pull/6583
[#6584]: https://github.com/contao/contao/pull/6584
[#6590]: https://github.com/contao/contao/pull/6590
[#6594]: https://github.com/contao/contao/pull/6594
[#6595]: https://github.com/contao/contao/pull/6595
[#6596]: https://github.com/contao/contao/pull/6596
[#6597]: https://github.com/contao/contao/pull/6597
[#6598]: https://github.com/contao/contao/pull/6598
[#6600]: https://github.com/contao/contao/pull/6600
[#6603]: https://github.com/contao/contao/pull/6603
[#6604]: https://github.com/contao/contao/pull/6604
[#6605]: https://github.com/contao/contao/pull/6605
[#6606]: https://github.com/contao/contao/pull/6606
[#6607]: https://github.com/contao/contao/pull/6607
[#6614]: https://github.com/contao/contao/pull/6614
[#6615]: https://github.com/contao/contao/pull/6615
[#6620]: https://github.com/contao/contao/pull/6620
[#6626]: https://github.com/contao/contao/pull/6626
[#6627]: https://github.com/contao/contao/pull/6627
[#6628]: https://github.com/contao/contao/pull/6628
[#6631]: https://github.com/contao/contao/pull/6631
[#6636]: https://github.com/contao/contao/pull/6636
[#6638]: https://github.com/contao/contao/pull/6638
[#6639]: https://github.com/contao/contao/pull/6639
[#6641]: https://github.com/contao/contao/pull/6641
[#6642]: https://github.com/contao/contao/pull/6642
[#6643]: https://github.com/contao/contao/pull/6643
[#6645]: https://github.com/contao/contao/pull/6645
[#6646]: https://github.com/contao/contao/pull/6646
[#6648]: https://github.com/contao/contao/pull/6648
[#6650]: https://github.com/contao/contao/pull/6650
[#6651]: https://github.com/contao/contao/pull/6651
[#6652]: https://github.com/contao/contao/pull/6652
[#6661]: https://github.com/contao/contao/pull/6661
[#6665]: https://github.com/contao/contao/pull/6665
[#6668]: https://github.com/contao/contao/pull/6668
[#6669]: https://github.com/contao/contao/pull/6669
[#6670]: https://github.com/contao/contao/pull/6670
[#6672]: https://github.com/contao/contao/pull/6672
[#6673]: https://github.com/contao/contao/pull/6673
[#6675]: https://github.com/contao/contao/pull/6675
[#6676]: https://github.com/contao/contao/pull/6676
[#6683]: https://github.com/contao/contao/pull/6683
[#6707]: https://github.com/contao/contao/pull/6707
[#6708]: https://github.com/contao/contao/pull/6708
[#6714]: https://github.com/contao/contao/pull/6714
[#6718]: https://github.com/contao/contao/pull/6718
[#6719]: https://github.com/contao/contao/pull/6719
[#6723]: https://github.com/contao/contao/pull/6723
[#6736]: https://github.com/contao/contao/pull/6736
[#6737]: https://github.com/contao/contao/pull/6737
[#6738]: https://github.com/contao/contao/pull/6738
[#6740]: https://github.com/contao/contao/pull/6740
[#6742]: https://github.com/contao/contao/pull/6742
[#6743]: https://github.com/contao/contao/pull/6743
[#6744]: https://github.com/contao/contao/pull/6744
[#6747]: https://github.com/contao/contao/pull/6747
[#6758]: https://github.com/contao/contao/pull/6758
[#6759]: https://github.com/contao/contao/pull/6759
[#6760]: https://github.com/contao/contao/pull/6760
[#6761]: https://github.com/contao/contao/pull/6761
[#6767]: https://github.com/contao/contao/pull/6767
[#6775]: https://github.com/contao/contao/pull/6775
[#6788]: https://github.com/contao/contao/pull/6788
[#6794]: https://github.com/contao/contao/pull/6794
[#6803]: https://github.com/contao/contao/pull/6803
[#6805]: https://github.com/contao/contao/pull/6805
[#6807]: https://github.com/contao/contao/pull/6807
[#6809]: https://github.com/contao/contao/pull/6809
[#6814]: https://github.com/contao/contao/pull/6814
[#6815]: https://github.com/contao/contao/pull/6815
[#6819]: https://github.com/contao/contao/pull/6819
[#6830]: https://github.com/contao/contao/pull/6830
[#6831]: https://github.com/contao/contao/pull/6831
[#6833]: https://github.com/contao/contao/pull/6833
[#6835]: https://github.com/contao/contao/pull/6835
[#6838]: https://github.com/contao/contao/pull/6838
[#6839]: https://github.com/contao/contao/pull/6839
[#6840]: https://github.com/contao/contao/pull/6840
[#6841]: https://github.com/contao/contao/pull/6841
[#6843]: https://github.com/contao/contao/pull/6843
[#6851]: https://github.com/contao/contao/pull/6851
[#6852]: https://github.com/contao/contao/pull/6852
[#6854]: https://github.com/contao/contao/pull/6854
[#6855]: https://github.com/contao/contao/pull/6855
[#6856]: https://github.com/contao/contao/pull/6856
[#6857]: https://github.com/contao/contao/pull/6857
[#6858]: https://github.com/contao/contao/pull/6858
[#6861]: https://github.com/contao/contao/pull/6861
[#6867]: https://github.com/contao/contao/pull/6867
[#6880]: https://github.com/contao/contao/pull/6880
[#6882]: https://github.com/contao/contao/pull/6882
[#6889]: https://github.com/contao/contao/pull/6889
[#6890]: https://github.com/contao/contao/pull/6890
[#6893]: https://github.com/contao/contao/pull/6893
[#6895]: https://github.com/contao/contao/pull/6895
[#6898]: https://github.com/contao/contao/pull/6898
[#6900]: https://github.com/contao/contao/pull/6900
[#6916]: https://github.com/contao/contao/pull/6916
[#6917]: https://github.com/contao/contao/pull/6917
[#6919]: https://github.com/contao/contao/pull/6919
[#6925]: https://github.com/contao/contao/pull/6925
[#6927]: https://github.com/contao/contao/pull/6927
[#6936]: https://github.com/contao/contao/pull/6936
[#6938]: https://github.com/contao/contao/pull/6938
[#6939]: https://github.com/contao/contao/pull/6939
[#6941]: https://github.com/contao/contao/pull/6941
[#6943]: https://github.com/contao/contao/pull/6943
[#6944]: https://github.com/contao/contao/pull/6944
[#6946]: https://github.com/contao/contao/pull/6946
[#6950]: https://github.com/contao/contao/pull/6950
[#6951]: https://github.com/contao/contao/pull/6951
[#6952]: https://github.com/contao/contao/pull/6952
[#6953]: https://github.com/contao/contao/pull/6953
[#6954]: https://github.com/contao/contao/pull/6954
[#6956]: https://github.com/contao/contao/pull/6956
[#6960]: https://github.com/contao/contao/pull/6960
[#6961]: https://github.com/contao/contao/pull/6961
[#6962]: https://github.com/contao/contao/pull/6962
[#6963]: https://github.com/contao/contao/pull/6963
[#6968]: https://github.com/contao/contao/pull/6968
[#6969]: https://github.com/contao/contao/pull/6969
[#6975]: https://github.com/contao/contao/pull/6975
[#6978]: https://github.com/contao/contao/pull/6978
[#6979]: https://github.com/contao/contao/pull/6979
[#6982]: https://github.com/contao/contao/pull/6982
[#6993]: https://github.com/contao/contao/pull/6993
[#6995]: https://github.com/contao/contao/pull/6995
[#6996]: https://github.com/contao/contao/pull/6996
[#7002]: https://github.com/contao/contao/pull/7002
[#7003]: https://github.com/contao/contao/pull/7003
[#7005]: https://github.com/contao/contao/pull/7005
[#7006]: https://github.com/contao/contao/pull/7006
[#7007]: https://github.com/contao/contao/pull/7007
[#7008]: https://github.com/contao/contao/pull/7008
[#7016]: https://github.com/contao/contao/pull/7016
[#7017]: https://github.com/contao/contao/pull/7017
[#7021]: https://github.com/contao/contao/pull/7021
[#7026]: https://github.com/contao/contao/pull/7026
[#7027]: https://github.com/contao/contao/pull/7027
[#7028]: https://github.com/contao/contao/pull/7028
[#7031]: https://github.com/contao/contao/pull/7031
[#7032]: https://github.com/contao/contao/pull/7032
[#7037]: https://github.com/contao/contao/pull/7037
[#7039]: https://github.com/contao/contao/pull/7039
[#7044]: https://github.com/contao/contao/pull/7044
[#7045]: https://github.com/contao/contao/pull/7045
[#7046]: https://github.com/contao/contao/pull/7046
[#7057]: https://github.com/contao/contao/pull/7057
[#7064]: https://github.com/contao/contao/pull/7064
[#7071]: https://github.com/contao/contao/pull/7071
[#7073]: https://github.com/contao/contao/pull/7073
[#7074]: https://github.com/contao/contao/pull/7074
[#7081]: https://github.com/contao/contao/pull/7081
[#7106]: https://github.com/contao/contao/pull/7106
[#7107]: https://github.com/contao/contao/pull/7107
[#7112]: https://github.com/contao/contao/pull/7112
[#7113]: https://github.com/contao/contao/pull/7113
[#7122]: https://github.com/contao/contao/pull/7122
