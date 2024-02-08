# Changelog

This project adheres to [Semantic Versioning].

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
[5.3.0-RC3]: https://github.com/contao/contao/releases/tag/5.3.0-RC3
[5.3.0-RC2]: https://github.com/contao/contao/releases/tag/5.3.0-RC2
[5.3.0-RC1]: https://github.com/contao/contao/releases/tag/5.3.0-RC1
[aschempp]: https://github.com/aschempp
[ausi]: https://github.com/ausi
[bezin]: https://github.com/bezin
[de-es]: https://github.com/de-es
[fritzmg]: https://github.com/fritzmg
[leofeyer]: https://github.com/leofeyer
[m-vo]: https://github.com/m-vo
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
