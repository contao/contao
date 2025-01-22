# Changelog

This project adheres to [Semantic Versioning].

## [5.5.0-RC2] (2025-01-22)

**Fixed issues:**

- [#7925] Make the Twig inspector aware of the `RuntimeThemeExpression` (part 2) ([m-vo])
- [#7901] Fix the Choices.js initialization ([fritzmg])
- [#7916] Inline the operations and picker ([zoglo])
- [#7914] Make the Twig inspector aware of the `RuntimeThemeExpression` ([m-vo])
- [#7909] Revert the changes to `FormSelect` regarding `Chosen` ([fritzmg])
- [#7897] Fix the context menu issues ([aschempp])
- [#7903] Fix a small formatting issue in the Template Studio ([m-vo])
- [#7886] Improve handling of Turbo stream requests when unauthenticated ([m-vo])

## [5.5.0-RC1] (2025-01-15)

**New features:**

- [#7686] Rework the `<dialog>` element ([zoglo])
- [#7839] Remove redundant title attributes ([leofeyer])
- [#7816] Add the context menu ([aschempp])
- [#7863] Show breadcrumbs as labels in the back end search ([ausi])
- [#7824] Replace `Chosen` with `Choices.js` ([zoglo])
- [#7594] Rewrite `tl_panel` and `tl_subpanel` to `display: flex` ([zoglo])
- [#7842] Add edit and view URLs for the back end search ([ausi])
- [#7817] Allow adding additional login providers to the back end login screen ([leofeyer])
- [#7835] Implement search invalidation on file storage DBAFS updates ([m-vo])
- [#7833] Fine-tune the template studio ([m-vo])
- [#7826] Split the `BackendMenuListener` class into two separate classes ([leofeyer])
- [#7851] Implement Flysystem default public URI support ([m-vo])
- [#7838] Make sure back end search related messages are never too big ([Toflar])
- [#7837] Use stable SEAL versions ([Toflar])
- [#7831] Use the `#[IsGranted]` PHP attribute in template studio controller ([fritzmg])
- [#7814] Pass on the information if a message has been triggered via the WebWorker ([Toflar])
- [#7829] Allow installing `scssphp/scssphp` version 2 ([zoglo])
- [#7792] Use the Stimulus color picker instead of the old MooTools one ([zoglo])
- [#7683] Support themes in the template studio ([m-vo])
- [#7818] Move the "continue" button on the login screen to the right ([leofeyer])
- [#7819] Correctly show the record label if `showColumns` is enabled ([ausi])
- [#7675] Add a Stimulus controller and the UI for the back end search ([zoglo])
- [#7811] Switch to new SEAL ReindexProvider framework ([Toflar])
- [#7796] Use PHP CMS-IG SEAL for the back end search ([Toflar])
- [#7684] Adjust the template studio layout ([zoglo])
- [#7769] Implement search invalidation on DC_Table edits ([Toflar])
- [#7761] Implement a re-index maintenance job for the back end search ([Toflar])
- [#7604] Implement subtitles and CC functionality for videos ([zoglo])
- [#7659] Move the bundle templates into the `@Contao` namespace ([m-vo])
- [#7681] Add IDE autocompletion for core templates ([m-vo])
- [#7721] Fix the delete API of the back end search ([Toflar])
- [#7738] Allow events to format DC_Table search results ([Toflar])
- [#7672] Add autocompletion for the template studio ([m-vo])
- [#7627] Use the VFS in the `FileProvider` of the back end search ([m-vo])
- [#7658] Add template studio operations to create and rename variant templates ([m-vo])
- [#7648] Extract the clipboard session handling ([aschempp])
- [#7651] Reduce code duplication and add a template for the buttons builder ([aschempp])
- [#7598] Add code lens and operations support to the template studio ([m-vo])
- [#7640] Implement deleting search documents ([Toflar])
- [#7642] Turbo request/response improvements ([m-vo])
- [#7643] Add the template studio config ([m-vo])
- [#7645] Add the buttons builder ([aschempp])
- [#7609] Use the data container operations builder ([aschempp])
- [#7571] Implement passkey authentication for the back end ([fritzmg])
- [#7635] Add the `FigureBuilder::fromFilesystemItem()` method ([m-vo])
- [#7621] Twig Finder improvements ([m-vo])
- [#7634] Add events to modify indexed documents and search hits ([Toflar])
- [#7616] Extract the duplicate header operations markup ([aschempp])
- [#7608] Hide the picker in "edit all" or clipboard mode ([aschempp])
- [#7607] Add a `DC_Table` search provider ([Toflar])
- [#7601] Improve the search logic ([Toflar])
- [#7613] Improve UX on edit multiple ([Toflar])
- [#7611] Refactor the ternary operator in header operations ([aschempp])
- [#7610] Allow row-highlighting in every view ([zoglo])
- [#7593] Add the data container operations builder ([aschempp])
- [#7592] Experimental foundation for a back end search ([Toflar])
- [#7589] Add the template studio editor ([m-vo])
- [#7588] Record labeler service ([ausi])
- [#7473] Add the `autocomplete` attribute to form fields ([zoglo])
- [#7586] Improve the profiler toolbar and panel ([ausi])
- [#7572] Add a `title` attribute to the `youtube` and `vimeo` element ([zoglo])
- [#7587] Add the foundation for the template studio ([m-vo])
- [#7584] Move the form field input validation fields into their own palette ([leofeyer])
- [#7470] Pass slider settings as a single JSON data attribute ([delirius])
- [#7580] Add a basic PostCSS setup ([m-vo])
- [#7566] Introduce a general cache tag invalidator service ([Toflar])
- [#7576] Highlight selected rows in edit multiple mode ([zoglo])
- [#7478] Simplify adding extensions to `contao.image.valid_extensions` ([zoglo])
- [#7573] Add a user templates VFS storage ([m-vo])
- [#7579] Make the Twig inspector understand the block hierarchy ([m-vo])
- [#7505] Make Twig 3.12 the minimum version ([leofeyer])
- [#7452] Unlock `dragonmantank/cron-expression` version 3 ([fritzmg])

**Fixed issues:**

- [#7853] Remove the ability to edit Twig templates in the old template editor ([m-vo])
- [#7864] Add error handling in the `backend-search-controller` ([zoglo])
- [#7866] Vote on the document instead of the hit in the back end search ([Toflar])
- [#7867] Adjust the `ClipboardManager::set()` method signature ([aschempp])
- [#7857] Remove an unnecessary line of code ([m-vo])
- [#7855] Use the DBAFS manager in `DC_Folder` ([m-vo])
- [#7850] Fix the SEAL index name ([Toflar])
- [#7834] Allow setting a custom redirect route when unauthenticated ([m-vo])
- [#7830] Fix `$models` within `ContaoDataCollector` being `null` ([zoglo])
- [#7780] Replace the `CacheTagInvalidator` service with the `CacheTagManager` ([aschempp])
- [#7813] Update the back end search UI ([zoglo])
- [#7819] Correctly show the record label if `showColumns` is enabled ([ausi])
- [#7600] Fix prefetching of edit, children and select links ([aschempp])
- [#7691] Fix the partials regex in the Twig template finder ([fritzmg])
- [#7701] Use the return value of the `InvalidateCacheTagsEvent` ([leofeyer])
- [#7673] Fix a type hint in the figure renderer ([m-vo])
- [#7649] Enable `pauseOnMouseEnter` by default ([fritzmg])
- [#7646] Fix a copy and paste error in tree mode ([aschempp])
- [#7582] Add PostCSS as described in Symfony Encore ([leofeyer])

[Semantic Versioning]: https://semver.org/spec/v2.0.0.html
[5.5.0-RC2]: https://github.com/contao/contao/releases/tag/5.5.0-RC2
[5.5.0-RC1]: https://github.com/contao/contao/releases/tag/5.5.0-RC1
[aschempp]: https://github.com/aschempp
[ausi]: https://github.com/ausi
[delirius]: https://github.com/delirius
[fritzmg]: https://github.com/fritzmg
[leofeyer]: https://github.com/leofeyer
[m-vo]: https://github.com/m-vo
[Toflar]: https://github.com/Toflar
[zoglo]: https://github.com/zoglo
[#7452]: https://github.com/contao/contao/pull/7452
[#7470]: https://github.com/contao/contao/pull/7470
[#7473]: https://github.com/contao/contao/pull/7473
[#7478]: https://github.com/contao/contao/pull/7478
[#7505]: https://github.com/contao/contao/pull/7505
[#7566]: https://github.com/contao/contao/pull/7566
[#7571]: https://github.com/contao/contao/pull/7571
[#7572]: https://github.com/contao/contao/pull/7572
[#7573]: https://github.com/contao/contao/pull/7573
[#7576]: https://github.com/contao/contao/pull/7576
[#7579]: https://github.com/contao/contao/pull/7579
[#7580]: https://github.com/contao/contao/pull/7580
[#7582]: https://github.com/contao/contao/pull/7582
[#7584]: https://github.com/contao/contao/pull/7584
[#7586]: https://github.com/contao/contao/pull/7586
[#7587]: https://github.com/contao/contao/pull/7587
[#7588]: https://github.com/contao/contao/pull/7588
[#7589]: https://github.com/contao/contao/pull/7589
[#7592]: https://github.com/contao/contao/pull/7592
[#7593]: https://github.com/contao/contao/pull/7593
[#7594]: https://github.com/contao/contao/pull/7594
[#7598]: https://github.com/contao/contao/pull/7598
[#7600]: https://github.com/contao/contao/pull/7600
[#7601]: https://github.com/contao/contao/pull/7601
[#7604]: https://github.com/contao/contao/pull/7604
[#7607]: https://github.com/contao/contao/pull/7607
[#7608]: https://github.com/contao/contao/pull/7608
[#7609]: https://github.com/contao/contao/pull/7609
[#7610]: https://github.com/contao/contao/pull/7610
[#7611]: https://github.com/contao/contao/pull/7611
[#7613]: https://github.com/contao/contao/pull/7613
[#7616]: https://github.com/contao/contao/pull/7616
[#7621]: https://github.com/contao/contao/pull/7621
[#7627]: https://github.com/contao/contao/pull/7627
[#7634]: https://github.com/contao/contao/pull/7634
[#7635]: https://github.com/contao/contao/pull/7635
[#7640]: https://github.com/contao/contao/pull/7640
[#7642]: https://github.com/contao/contao/pull/7642
[#7643]: https://github.com/contao/contao/pull/7643
[#7645]: https://github.com/contao/contao/pull/7645
[#7646]: https://github.com/contao/contao/pull/7646
[#7648]: https://github.com/contao/contao/pull/7648
[#7649]: https://github.com/contao/contao/pull/7649
[#7651]: https://github.com/contao/contao/pull/7651
[#7658]: https://github.com/contao/contao/pull/7658
[#7659]: https://github.com/contao/contao/pull/7659
[#7672]: https://github.com/contao/contao/pull/7672
[#7673]: https://github.com/contao/contao/pull/7673
[#7675]: https://github.com/contao/contao/pull/7675
[#7681]: https://github.com/contao/contao/pull/7681
[#7683]: https://github.com/contao/contao/pull/7683
[#7684]: https://github.com/contao/contao/pull/7684
[#7686]: https://github.com/contao/contao/pull/7686
[#7691]: https://github.com/contao/contao/pull/7691
[#7701]: https://github.com/contao/contao/pull/7701
[#7721]: https://github.com/contao/contao/pull/7721
[#7738]: https://github.com/contao/contao/pull/7738
[#7761]: https://github.com/contao/contao/pull/7761
[#7769]: https://github.com/contao/contao/pull/7769
[#7780]: https://github.com/contao/contao/pull/7780
[#7792]: https://github.com/contao/contao/pull/7792
[#7796]: https://github.com/contao/contao/pull/7796
[#7811]: https://github.com/contao/contao/pull/7811
[#7813]: https://github.com/contao/contao/pull/7813
[#7814]: https://github.com/contao/contao/pull/7814
[#7816]: https://github.com/contao/contao/pull/7816
[#7817]: https://github.com/contao/contao/pull/7817
[#7818]: https://github.com/contao/contao/pull/7818
[#7819]: https://github.com/contao/contao/pull/7819
[#7824]: https://github.com/contao/contao/pull/7824
[#7826]: https://github.com/contao/contao/pull/7826
[#7829]: https://github.com/contao/contao/pull/7829
[#7830]: https://github.com/contao/contao/pull/7830
[#7831]: https://github.com/contao/contao/pull/7831
[#7833]: https://github.com/contao/contao/pull/7833
[#7834]: https://github.com/contao/contao/pull/7834
[#7835]: https://github.com/contao/contao/pull/7835
[#7837]: https://github.com/contao/contao/pull/7837
[#7838]: https://github.com/contao/contao/pull/7838
[#7839]: https://github.com/contao/contao/pull/7839
[#7842]: https://github.com/contao/contao/pull/7842
[#7850]: https://github.com/contao/contao/pull/7850
[#7851]: https://github.com/contao/contao/pull/7851
[#7853]: https://github.com/contao/contao/pull/7853
[#7855]: https://github.com/contao/contao/pull/7855
[#7857]: https://github.com/contao/contao/pull/7857
[#7863]: https://github.com/contao/contao/pull/7863
[#7864]: https://github.com/contao/contao/pull/7864
[#7866]: https://github.com/contao/contao/pull/7866
[#7867]: https://github.com/contao/contao/pull/7867
[#7886]: https://github.com/contao/contao/pull/7886
[#7897]: https://github.com/contao/contao/pull/7897
[#7901]: https://github.com/contao/contao/pull/7901
[#7903]: https://github.com/contao/contao/pull/7903
[#7909]: https://github.com/contao/contao/pull/7909
[#7914]: https://github.com/contao/contao/pull/7914
[#7916]: https://github.com/contao/contao/pull/7916
[#7925]: https://github.com/contao/contao/pull/7925
