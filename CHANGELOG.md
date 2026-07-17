# Changelog

This project adheres to [Semantic Versioning].

## [6.0.0-RC1] (2026-07-17)

**New features:**

- [#10018] Add a reusable "column to virtual" migration ([leofeyer])
- [#9584] Improve the UX of the tooltip controller ([zoglo])
- [#10013] Return the default values from the schema in models ([ausi])
- [#9999] Add ARIA labels where there is no `<label>` element ([leofeyer])
- [#9983] Allow Symfony 8 ([Toflar])
- [#10001] Remove the deprecated `Backend.Theme` scripts that already have Stimulus alternatives ([zoglo])
- [#9992] Add a simple token filter for Twig ([ausi])
- [#8349] Add a "paste into" operation to the `DC_Table` parent view ([lukasbableck])
- [#9548] Remove the legacy migrations from the Stimulus controllers ([zoglo])
- [#9540] Remove the deprecations from the calendar bundle ([zoglo])
- [#9981] Only add custom form field classes to the outer container ([leofeyer])
- [#9565] Allow adding a primary image to a page and use it in the JSON-LD ([lukasbableck])
- [#9962] Remove the deprecated child record callback ([ausi])
- [#9944] Deprecate `Input::get('language')` ([ausi])
- [#9947] Remove obsolete widget attributes ([ausi])
- [#9557] Add the output encoding migration ([ausi])
- [#9576] Add an option to change news JSON-LD type ([lukasbableck])
- [#9946] Convert basic entities to Unicode for non-HTML fields ([ausi])
- [#9945] Remove the `Input::setPost()` usages ([ausi])
- [#9943] Remove `Input::setGet()` from `DC_Table` ([ausi])
- [#9942] Deprecate most methods of the `Input` class ([ausi])
- [#9930] Do not include country codes in `tl_member.language` ([ausi])
- [#9941] Do not use `Input::setGet()` in the search module ([ausi])
- [#9938] Enable double encoding ([ausi])
- [#7430] Replace more `$GLOBALS['objPage']` with the page finder service ([leofeyer])
- [#7014] Replace `$GLOBALS['objPage']` with the page finder ([leofeyer])
- [#9582] Use the `manifest.json` file in the `Image::getHtml()` method ([zoglo])
- [#9534] Deprecate extending from `Module`, `ContentElement` and `Hybrid` ([Toflar])
- [#9541] Remove the deprecations from the comments bundle ([zoglo])
- [#9542] Remove the deprecations from the FAQ bundle ([zoglo])
- [#9543] Remove the deprecations from the manager bundle ([zoglo])
- [#9544] Remove the deprecations from the news bundle ([zoglo])
- [#9545] Remove the deprecations from the newsletter bundle ([zoglo])
- [#9572] Remove the deprecated `customSchemaOptions` support ([fritzmg])
- [#9577] Implement a proper `{{file::*}}` insert tag based on the VFS ([Toflar])
- [#9551] Rename the user templates namespace to `@Contao_User` ([m-vo])
- [#9536] Drop the deprecated `FilterPageTypeEvent` service ([Toflar])
- [#9599] Rename `var/logs` to `var/log` ([leofeyer])
- [#9566] Remove the Symfony bundle templates ([m-vo])
- [#9546] Remove the bundle migrations ([zoglo])
- [#9556] Remove Swift Mailer support ([bytehead])
- [#9558] Drop support for `doctrine/dbal` v3 ([Toflar])
- [#9535] Drop the deprecated `EntityCacheTags` service ([Toflar])
- [#9533] Add VFS public Uri handling capability to the player ([fritzmg])
- [#9498] Rework the public file handling in the VFS ([Toflar])
- [#9524] Drop the `RenderPageEvent` ([Toflar])
- [#9505] Remove input encoding in the `Widget` class ([ausi])
- [#9485] Move the `flexible` theme into the build chain ([zoglo])
- [#9484] Remove the legacy template system ([m-vo])
- [#9332] Use PHP native lazy objects ([fritzmg])
- [#9414] Change all SQL definitions to array notation ([fritzmg])
- [#9482] Remove merging legacy headers ([Toflar])
- [#9480] Remove the global request token for templates ([aschempp])
- [#9483] Drop the deprecated message priority interfaces ([Toflar])
- [#9479] Remove support for service annotations ([aschempp])

**Fixed issues:**

- [#10030] Replace the remaining `Input` usages ([ausi])
- [#10029] Encode user properties in the backend header menu ([ausi])
- [#10027] Update the `DEPRECATED.md` and `UPGRADE.md` ([leofeyer])
- [#10023] Stop using the deprecated Doctrine DBAL methods ([leofeyer])
- [#10022] Remove the `generate()` method from frontend form widgets ([ausi])
- [#10020] Add more output encoding ([ausi])
- [#10021] Remove unused `Backend` methods ([ausi])
- [#10019] Do not return `null` for non-null columns in models ([ausi])
- [#10006] Remove the `Input` class from the `DcaUrlAnalyzer`, operations, the palette builder, and several listeners ([ausi])
- [#9997] Autolink URLs in comments and make them plain text ([ausi])
- [#9977] Change "Contao 6" to "Contao 7" in the deprecation messages ([leofeyer])
- [#9988] Remove old configuration nodes deprecated since Contao 4 ([leofeyer])
- [#9989] Wrap the active breadcrumb item in `<strong>` tags ([leofeyer])
- [#9994] Remove BBCode ([ausi])
- [#9990] Move to a newer DBAFS hashing algorithm ([m-vo])
- [#9986] Prevent changing the password in the "personal data" module ([leofeyer])
- [#9987] Deprecate `$strIp` in user classes ([fritzmg])
- [#9980] Generate the form data XML file using `DOMDocument` ([leofeyer])
- [#9979] Remove the setters from the `RecordLabel` class ([ausi])
- [#9969] Encode and sanitize widgets and options ([ausi])
- [#9966] Add a `RecordLabel` object for the label callback ([ausi])
- [#9974] Ensure that ampersands in URLs are correctly encoded ([leofeyer])
- [#9968] Do not encode ampersands in the back button URL ([leofeyer])
- [#9965] Use `findById()` instead of `getRelated()` ([leofeyer])
- [#9967] Encode labels in the "show" modal ([ausi])
- [#9963] Encode the group header in the parent view ([ausi])
- [#9952] Correctly encode the content label ([ausi])
- [#9954] Properly encode member group labels ([ausi])
- [#9892] Fix the missing `$objPage` in `FrontendTemplate::setCacheHeaders` ([aschempp])
- [#9628] Use a delegate template when rendering Twig components ([m-vo])
- [#9578] Fix the `FigureRenderer` default template ([m-vo])
- [#9568] Remove all usages of `StringUtil::ampersand()` ([m-vo])
- [#9506] Use the response context to generate the nonce ([bytehead])
- [#9497] Increase the minimum Doctrine ORM version ([fritzmg])

[Semantic Versioning]: https://semver.org/spec/v2.0.0.html
[6.0.0-RC1]: https://github.com/contao/contao/releases/tag/6.0.0-RC1
[aschempp]: https://github.com/aschempp
[ausi]: https://github.com/ausi
[bytehead]: https://github.com/bytehead
[fritzmg]: https://github.com/fritzmg
[leofeyer]: https://github.com/leofeyer
[lukasbableck]: https://github.com/lukasbableck
[m-vo]: https://github.com/m-vo
[Toflar]: https://github.com/Toflar
[zoglo]: https://github.com/zoglo
[#7014]: https://github.com/contao/contao/pull/7014
[#7430]: https://github.com/contao/contao/pull/7430
[#8349]: https://github.com/contao/contao/pull/8349
[#9332]: https://github.com/contao/contao/pull/9332
[#9414]: https://github.com/contao/contao/pull/9414
[#9479]: https://github.com/contao/contao/pull/9479
[#9480]: https://github.com/contao/contao/pull/9480
[#9482]: https://github.com/contao/contao/pull/9482
[#9483]: https://github.com/contao/contao/pull/9483
[#9484]: https://github.com/contao/contao/pull/9484
[#9485]: https://github.com/contao/contao/pull/9485
[#9497]: https://github.com/contao/contao/pull/9497
[#9498]: https://github.com/contao/contao/pull/9498
[#9505]: https://github.com/contao/contao/pull/9505
[#9506]: https://github.com/contao/contao/pull/9506
[#9524]: https://github.com/contao/contao/pull/9524
[#9533]: https://github.com/contao/contao/pull/9533
[#9534]: https://github.com/contao/contao/pull/9534
[#9535]: https://github.com/contao/contao/pull/9535
[#9536]: https://github.com/contao/contao/pull/9536
[#9540]: https://github.com/contao/contao/pull/9540
[#9541]: https://github.com/contao/contao/pull/9541
[#9542]: https://github.com/contao/contao/pull/9542
[#9543]: https://github.com/contao/contao/pull/9543
[#9544]: https://github.com/contao/contao/pull/9544
[#9545]: https://github.com/contao/contao/pull/9545
[#9546]: https://github.com/contao/contao/pull/9546
[#9548]: https://github.com/contao/contao/pull/9548
[#9551]: https://github.com/contao/contao/pull/9551
[#9556]: https://github.com/contao/contao/pull/9556
[#9557]: https://github.com/contao/contao/pull/9557
[#9558]: https://github.com/contao/contao/pull/9558
[#9565]: https://github.com/contao/contao/pull/9565
[#9566]: https://github.com/contao/contao/pull/9566
[#9568]: https://github.com/contao/contao/pull/9568
[#9572]: https://github.com/contao/contao/pull/9572
[#9576]: https://github.com/contao/contao/pull/9576
[#9577]: https://github.com/contao/contao/pull/9577
[#9578]: https://github.com/contao/contao/pull/9578
[#9582]: https://github.com/contao/contao/pull/9582
[#9584]: https://github.com/contao/contao/pull/9584
[#9599]: https://github.com/contao/contao/pull/9599
[#9628]: https://github.com/contao/contao/pull/9628
[#9892]: https://github.com/contao/contao/pull/9892
[#9930]: https://github.com/contao/contao/pull/9930
[#9938]: https://github.com/contao/contao/pull/9938
[#9941]: https://github.com/contao/contao/pull/9941
[#9942]: https://github.com/contao/contao/pull/9942
[#9943]: https://github.com/contao/contao/pull/9943
[#9944]: https://github.com/contao/contao/pull/9944
[#9945]: https://github.com/contao/contao/pull/9945
[#9946]: https://github.com/contao/contao/pull/9946
[#9947]: https://github.com/contao/contao/pull/9947
[#9952]: https://github.com/contao/contao/pull/9952
[#9954]: https://github.com/contao/contao/pull/9954
[#9962]: https://github.com/contao/contao/pull/9962
[#9963]: https://github.com/contao/contao/pull/9963
[#9965]: https://github.com/contao/contao/pull/9965
[#9966]: https://github.com/contao/contao/pull/9966
[#9967]: https://github.com/contao/contao/pull/9967
[#9968]: https://github.com/contao/contao/pull/9968
[#9969]: https://github.com/contao/contao/pull/9969
[#9974]: https://github.com/contao/contao/pull/9974
[#9977]: https://github.com/contao/contao/pull/9977
[#9979]: https://github.com/contao/contao/pull/9979
[#9980]: https://github.com/contao/contao/pull/9980
[#9981]: https://github.com/contao/contao/pull/9981
[#9983]: https://github.com/contao/contao/pull/9983
[#9986]: https://github.com/contao/contao/pull/9986
[#9987]: https://github.com/contao/contao/pull/9987
[#9988]: https://github.com/contao/contao/pull/9988
[#9989]: https://github.com/contao/contao/pull/9989
[#9990]: https://github.com/contao/contao/pull/9990
[#9992]: https://github.com/contao/contao/pull/9992
[#9994]: https://github.com/contao/contao/pull/9994
[#9997]: https://github.com/contao/contao/pull/9997
[#9999]: https://github.com/contao/contao/pull/9999
[#10001]: https://github.com/contao/contao/pull/10001
[#10006]: https://github.com/contao/contao/pull/10006
[#10013]: https://github.com/contao/contao/pull/10013
[#10018]: https://github.com/contao/contao/pull/10018
[#10019]: https://github.com/contao/contao/pull/10019
[#10020]: https://github.com/contao/contao/pull/10020
[#10021]: https://github.com/contao/contao/pull/10021
[#10022]: https://github.com/contao/contao/pull/10022
[#10023]: https://github.com/contao/contao/pull/10023
[#10027]: https://github.com/contao/contao/pull/10027
[#10029]: https://github.com/contao/contao/pull/10029
[#10030]: https://github.com/contao/contao/pull/10030
