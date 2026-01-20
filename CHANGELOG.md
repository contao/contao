# Changelog

This project adheres to [Semantic Versioning].

## [5.7.0-RC1] (2026-01-20)

**New features:**

- [#9218] Cache the module access in the `TableAccessVoter` ([aschempp])
- [#8020] Add the `TwoFactorController` as content element ([bytehead])
- [#9268] Update the Node packages ([leofeyer])
- [#8816] Add the `ChangePasswordController` as content element ([bytehead])
- [#8801] Add the `CloseAccountController` as content element ([bytehead])
- [#9147] Implement tree trail support for the back end breadcrumb navigation ([zoglo])
- [#9186] Add scroll buttons to the jump targets navigation ([zoglo])
- [#9192] Allow opening the back end search with keyboard shortcuts ([zoglo])
- [#9193] Improve the UX and UI for the filters ([zoglo])
- [#8865] Use ALTCHA's floating UI ([zoglo])
- [#9212] Add icons to the table picker ([de-es])
- [#9181] Show the current trail when opening the accessible navigation on mobile ([zoglo])
- [#9187] Show a warning in the SERP widget when using `noindex` ([zoglo])
- [#9184] Stop using prefixed properties for `appearance` ([zoglo])
- [#9171] Add templates for `DC_Table::generateTree()` ([diekatrin])
- [#9170] Add templates for `DC_Table::parentView()`  ([m-vo])
- [#9208] Introduce the `ForeignKeyParser` service ([Toflar])
- [#9217] Deprecate the `trbl` widget ([fritzmg])
- [#8838] Implement virtual field support with a JSON storage ([fritzmg])
- [#9215] Allow closing Template Studio tabs via middle click ([lukasbableck])
- [#9012] Add DCA permissions ([aschempp])
- [#9150] Dynamically update the job view ([m-vo])
- [#9195] Use a datalist for the crawl member ([aschempp])
- [#9173] Introduce a `$config['backendSearch']` DCA setting ([Toflar])
- [#9094] Drop support for `scheb/2fa-*` `^6.0` and allow `^8.0` ([bytehead])
- [#9130] Add templates for `DC_Table::treeView()` ([diekatrin])
- [#9086] Add templates for `DC_Table::listView()` ([diekatrin])
- [#9154] Autosubmit the filters in the back end ([Toflar])
- [#8808] Move the theme toggle into the profile dropdown and remove the user prefix ([zoglo])
- [#9151] Move the back end controllers into their respective directory ([m-vo])
- [#9135] Surrogate template interoperability in the Template Studio ([m-vo])
- [#9153] Improve the slot tag DX ([m-vo])
- [#9148] Fullscreen mode for the Template Studio ([m-vo])
- [#9021] Enable the "override all" mode if a `DC_Folder` is DB-assisted ([aschempp])
- [#9034] Improve the articles node operation ([aschempp])
- [#9134] Add a "block" operation to newsletter recipients ([de-es])
- [#8826] Migrate the legacy crawl logic to the new jobs framework ([Toflar])
- [#8778] Migrate the database when deleting variant templates in the Template Studio ([m-vo])
- [#8200] Improve how the `ide-twig.json` file is generated ([m-vo])
- [#8850] Move the filters to the right side ([zoglo])
- [#8802] Ace improvements for the Template Studio ([m-vo])
- [#8971] Add a "contao-main" Turbo frame and apply filters to it ([Toflar])
- [#9065] Add tree trails support to the `DcaUrlAnalyzer` ([ausi])
- [#9110] Add `contao/loupe-bridge` to the monorepo ([Toflar])
- [#9095] Style the jobs widget nicely ([Toflar])
- [#9099] Use the translator in `System::getFormattedNumber()` ([Toflar])
- [#9083] Integrate the message bus in the jobs framework for better DX ([Toflar])
- [#9103] Remove the schema configuration for messenger transports ([fritzmg])
- [#9026] Add templates for `DC_Table::searchMenu()`, `DC_Table::sortMenu()`, `DC_Table::limitMenu()` and `DC_Table::filterMenu()` ([diekatrin])
- [#9055] Add a CS linter/fixer for Twig ([m-vo])
- [#9016] Support downloading multiple job attachments ([Toflar])
- [#9030] Add a formatter for DCA values ([aschempp])
- [#9067] Add a cancel link to the password change dialog ([aschempp])
- [#9073] Add the request object(s) to the request stack in the constructor ([Toflar])
- [#9074] Upgrade the service linter to Symfony 7.4 ([Toflar])
- [#9071] Require Symfony `^7.4` ([Toflar])
- [#9046] Add a Twig equivalent for every template ([m-vo])
- [#8967] Add an option to limit the back end width in the user profile ([leofeyer])
- [#9018] Remove the `targetURLAfterRedirectFetch` hotfix ([zoglo])
- [#8890] Implement `multipleFiles` option in file upload form field ([lukasbableck])
- [#8357] Add an optional "path" argument to the `DebugDcaCommand` ([Tastaturberuf])
- [#8844] Add a diff button to the operations menu ([aschempp])
- [#9005] Add templates for `DC_Table::editAll()` and  `DC_Table::overrideAll()` ([m-vo])
- [#8834] Move the favorites button to the breadcrumb ([aschempp])
- [#8821] Introduce a simple, more modern pagination ([fritzmg])
- [#9013] Add progress for the back end search jobs ([Toflar])
- [#8781] Introduce the row wizard ([zoglo])
- [#9002] Apply the border radius to the preview images, too ([leofeyer])
- [#8849] Add a progress bar to the jobs framework ([Toflar])
- [#8981] Add templates for `DC_Table::edit()` ([m-vo])
- [#8804] Add facets to the back end search ([Toflar])
- [#8975] Add templates for `DC_Table::show()` and `DC_Table::showAll()` ([m-vo])
- [#7562] Add more default values to the autogenerated `.env` files ([fritzmg])
- [#8907] Deprecate the `child_record_callback` ([aschempp])
- [#8922] Add back end search data to the Contao data collector ([fritzmg])
- [#8955] Use a listener to filter member groups ([aschempp])
- [#8830] Add job status helpers ([Toflar])
- [#8606] Use private elements in Javascript ([aschempp])
- [#8818] Implement attachments for the jobs framework ([Toflar])
- [#8901] Add the `referrerpolicy` attribute to the `_video.html.twig` template ([bright-cloud-studio])
- [#8927] Add `|default` to `toolbar_attributes` to fix "Variable does not exist" error ([lukasbableck])
- [#8815] Upgrade to PHP 8.3 and PHPUnit 12.4 ([leofeyer])
- [#8658] Use the `password-visibility` Stimulus component in the `password` widgets ([zoglo])
- [#8646] Use the `textarea-autogrow` Stimulus component ([zoglo])
- [#8852] Add blocks to the toolbar templates ([fritzmg])
- [#8904] Make two `DC_Table` methods protected ([aschempp])
- [#8827] Remove the "save and back" button ([leofeyer])
- [#8584] Add file icons to the back end preview of the download elements ([fritzmg])
- [#8615] Rewrite the Stimulus `check-all-controller` ([zoglo])
- [#8831] Add links to the back end breadcrumb for views with a `key` parameter ([ausi])
- [#8860] Add `@stylistic/stylelint` and configuration to lint the CSS files ([zoglo])
- [#8840] Add Contao form type basics ([bytehead])
- [#8618] Introduce the Stimulus `toggle-state-controller` ([zoglo])
- [#8832] Remove the dark left column in light mode ([leofeyer])
- [#8836] Run the Webpack dev-server with HTTPS ([zoglo])
- [#8835] Bump the minimum Symfony version to 7.3 ([Toflar])
- [#8833] Bump `bacon-qr-code` to version 3 ([zoglo])
- [#8817] Switch to hierarchical back links in the back end ([ausi])
- [#8825] Add a helper method for the job progress based on amounts ([Toflar])
- [#8548] Move the drag handle in the file manager to the left side ([aschempp])
- [#8494] Re-add `webpack-dev-server` and configure hot module replacement ([zoglo])
- [#8799] Do not render empty labels in widgets ([zoglo])
- [#8785] Use the Imagine info provider for format detection ([ausi])
- [#8786] Add new basic entities `[lsqb]` and `[rsqb]` ([ausi])
- [#8630] Implemented searching by UUID in the file manager ([Toflar])
- [#8608] Add support for YouTube's `mute=1` parameter ([fritzmg])

**Fixed issues:**

- [#9269] Fix the title for the `close_account` element ([fritzmg])
- [#9262] Bump the `hotwired/turbo` version ([m-vo])
- [#9257] Apply the missing changes to the new Twig templates ([ausi])
- [#9256] Also test for `iPhone` and `iPad` in the deep link controller ([leofeyer])
- [#9253] Refactor the `DC_Table` record listing templates ([m-vo])
- [#9247] Ignore DCAs whose tables are defined via a Doctrine entity ([lukasbableck])
- [#9252] Fix missing virtual fields handler service argument ([Toflar])
- [#9248] Ignore `#tl_limit` for the filter count ([fritzmg])
- [#9251] Fix the submit button order in the data container panel ([m-vo])
- [#9236] Fix rendering empty DC views ([aschempp])
- [#9105] Fix missing template fields for forms ([aschempp])
- [#9241] Fix the `enable` action for the row wizard ([zoglo])
- [#9197] Add `filePicker` and `pageTree` support to the row wizard ([zoglo])
- [#9233] Fix the row variable in parent mode ([aschempp])
- [#9230] Fix popup for wildcard links ([aschempp])
- [#9223] Use the drag handle label from the current table ([de-es])
- [#9224] Rename abstract form type to fit Symfony naming schema ([bytehead])
- [#9227] Use virtual fields for the new `tl_content` fields ([fritzmg])
- [#9222] Remove autoconfigured tags ([aschempp])
- [#9221] Remove leftover permissions ([aschempp])
- [#9219] Add the request token to URLs with parent node parameter ([ausi])
- [#9096] Allow HTML in checkbox and radio labels ([aschempp])
- [#9210] Allow HTML in search headers ([aschempp])
- [#9160] Improve the button accessibility and the filter panel ([aschempp])
- [#9196] Fix the `be_two_factor.html.twig` template ([fritzmg])
- [#9185] Do not add a border radius to the pagination menu inside a form ([leofeyer])
- [#9178] Fix the breadcrumb in the tree view ([zoglo])
- [#9169] Disallow undo preview interaction ([fritzmg])
- [#9167] Update the color switch label on click ([zoglo])
- [#9164] Reintroduce the "check all" controller to the "edit multiple" view ([zoglo])
- [#9145] Also use a fieldset for filters in `DC_Folder` ([zoglo])
- [#9136] Do not style the pagination in the back end preview ([fritzmg])
- [#9156] Always render favorites from the templates ([m-vo])
- [#9157] Don't apply `width: 1%` in the row wizard if there is no drag handle ([zoglo])
- [#9117] Use POST requests for the back end pagination ([aschempp])
- [#9146] Check if the Stimulus targets exist before applying changes ([zoglo])
- [#9152] Rebuild the template hierarchy in the layout template migration ([m-vo])
- [#9139] Merge an existing `Vary` header ([fritzmg])
- [#9138] Remove surrogate template for previously removed original template ([m-vo])
- [#9137] Set the jump targets value in the edit mask ([zoglo])
- [#9118] Fix custom backend controllers ([aschempp])
- [#9131] Add `Vary` header for back end responses ([fritzmg])
- [#9127] Restore the mobile menu behavior ([zoglo])
- [#9125] Fix some autofocus quirks ([zoglo])
- [#9124] Fix whitespace in listing layout caused by the operations menu ([zoglo])
- [#9115] Remove the `loupe-seal-adapter` from the manager bundle ([aschempp])
- [#9120] Fix using the deprecated `DependencyInjection` extension ([aschempp])
- [#9108] Show the loading box while saving the file and page tree ([aschempp])
- [#9109] Fix the missing favorites handle ([Toflar])
- [#9107] Make the optional services non-optional again ([leofeyer])
- [#9085] Improve the extension compatibility of `DC_Table` templates ([aschempp])
- [#9084] Use POST method for undo operation ([aschempp])
- [#9066] Remove the back button from Twig templates ([aschempp])
- [#9056] Add the missing `toggle-state-controller` to `be_main.html.twig` ([zoglo])
- [#9040] Set `nameValue` for widgets using the `row-wizard-controller` ([zoglo])
- [#9036] Fix the back end pagination ([fritzmg])
- [#9022] Fix the template migration ([aschempp])
- [#9006] Do not toggle disabled fields on row click ([zoglo])
- [#8999] Do not apply `overflow: clip` to `#main .content` ([leofeyer])
- [#8998] Do not check for the `noresize` class in the SERP preview controller ([zoglo])
- [#8980] Always generate `DC_Table::panel()` first ([m-vo])
- [#8962] Simplify the `contao_collector.html.twig` template ([leofeyer])
- [#8961] Fix HTML escaping for SQLite supported extensions ([stefansl])
- [#8910] Drop the experimental state for our Twig integration ([m-vo])
- [#8906] Fix the border radius of the legacy wrapper elements ([aschempp])
- [#8891] Load the form config ([bytehead])
- [#8896] Remove a superfluous `"` in the data-action attribute of the `be_main` template ([zoglo])

[Semantic Versioning]: https://semver.org/spec/v2.0.0.html
[5.7.0-RC1]: https://github.com/contao/contao/releases/tag/5.7.0-RC1
[aschempp]: https://github.com/aschempp
[ausi]: https://github.com/ausi
[bright-cloud-studio]: https://github.com/bright-cloud-studio
[bytehead]: https://github.com/bytehead
[de-es]: https://github.com/de-es
[diekatrin]: https://github.com/diekatrin
[fritzmg]: https://github.com/fritzmg
[leofeyer]: https://github.com/leofeyer
[lukasbableck]: https://github.com/lukasbableck
[m-vo]: https://github.com/m-vo
[stefansl]: https://github.com/stefansl
[Tastaturberuf]: https://github.com/Tastaturberuf
[Toflar]: https://github.com/Toflar
[zoglo]: https://github.com/zoglo
[#7562]: https://github.com/contao/contao/pull/7562
[#8020]: https://github.com/contao/contao/pull/8020
[#8200]: https://github.com/contao/contao/pull/8200
[#8357]: https://github.com/contao/contao/pull/8357
[#8494]: https://github.com/contao/contao/pull/8494
[#8548]: https://github.com/contao/contao/pull/8548
[#8584]: https://github.com/contao/contao/pull/8584
[#8606]: https://github.com/contao/contao/pull/8606
[#8608]: https://github.com/contao/contao/pull/8608
[#8615]: https://github.com/contao/contao/pull/8615
[#8618]: https://github.com/contao/contao/pull/8618
[#8630]: https://github.com/contao/contao/pull/8630
[#8646]: https://github.com/contao/contao/pull/8646
[#8658]: https://github.com/contao/contao/pull/8658
[#8778]: https://github.com/contao/contao/pull/8778
[#8781]: https://github.com/contao/contao/pull/8781
[#8785]: https://github.com/contao/contao/pull/8785
[#8786]: https://github.com/contao/contao/pull/8786
[#8799]: https://github.com/contao/contao/pull/8799
[#8801]: https://github.com/contao/contao/pull/8801
[#8802]: https://github.com/contao/contao/pull/8802
[#8804]: https://github.com/contao/contao/pull/8804
[#8808]: https://github.com/contao/contao/pull/8808
[#8815]: https://github.com/contao/contao/pull/8815
[#8816]: https://github.com/contao/contao/pull/8816
[#8817]: https://github.com/contao/contao/pull/8817
[#8818]: https://github.com/contao/contao/pull/8818
[#8821]: https://github.com/contao/contao/pull/8821
[#8825]: https://github.com/contao/contao/pull/8825
[#8826]: https://github.com/contao/contao/pull/8826
[#8827]: https://github.com/contao/contao/pull/8827
[#8830]: https://github.com/contao/contao/pull/8830
[#8831]: https://github.com/contao/contao/pull/8831
[#8832]: https://github.com/contao/contao/pull/8832
[#8833]: https://github.com/contao/contao/pull/8833
[#8834]: https://github.com/contao/contao/pull/8834
[#8835]: https://github.com/contao/contao/pull/8835
[#8836]: https://github.com/contao/contao/pull/8836
[#8838]: https://github.com/contao/contao/pull/8838
[#8840]: https://github.com/contao/contao/pull/8840
[#8844]: https://github.com/contao/contao/pull/8844
[#8849]: https://github.com/contao/contao/pull/8849
[#8850]: https://github.com/contao/contao/pull/8850
[#8852]: https://github.com/contao/contao/pull/8852
[#8860]: https://github.com/contao/contao/pull/8860
[#8865]: https://github.com/contao/contao/pull/8865
[#8890]: https://github.com/contao/contao/pull/8890
[#8891]: https://github.com/contao/contao/pull/8891
[#8896]: https://github.com/contao/contao/pull/8896
[#8901]: https://github.com/contao/contao/pull/8901
[#8904]: https://github.com/contao/contao/pull/8904
[#8906]: https://github.com/contao/contao/pull/8906
[#8907]: https://github.com/contao/contao/pull/8907
[#8910]: https://github.com/contao/contao/pull/8910
[#8922]: https://github.com/contao/contao/pull/8922
[#8927]: https://github.com/contao/contao/pull/8927
[#8955]: https://github.com/contao/contao/pull/8955
[#8961]: https://github.com/contao/contao/pull/8961
[#8962]: https://github.com/contao/contao/pull/8962
[#8967]: https://github.com/contao/contao/pull/8967
[#8971]: https://github.com/contao/contao/pull/8971
[#8975]: https://github.com/contao/contao/pull/8975
[#8980]: https://github.com/contao/contao/pull/8980
[#8981]: https://github.com/contao/contao/pull/8981
[#8998]: https://github.com/contao/contao/pull/8998
[#8999]: https://github.com/contao/contao/pull/8999
[#9002]: https://github.com/contao/contao/pull/9002
[#9005]: https://github.com/contao/contao/pull/9005
[#9006]: https://github.com/contao/contao/pull/9006
[#9012]: https://github.com/contao/contao/pull/9012
[#9013]: https://github.com/contao/contao/pull/9013
[#9016]: https://github.com/contao/contao/pull/9016
[#9018]: https://github.com/contao/contao/pull/9018
[#9021]: https://github.com/contao/contao/pull/9021
[#9022]: https://github.com/contao/contao/pull/9022
[#9026]: https://github.com/contao/contao/pull/9026
[#9030]: https://github.com/contao/contao/pull/9030
[#9034]: https://github.com/contao/contao/pull/9034
[#9036]: https://github.com/contao/contao/pull/9036
[#9040]: https://github.com/contao/contao/pull/9040
[#9046]: https://github.com/contao/contao/pull/9046
[#9055]: https://github.com/contao/contao/pull/9055
[#9056]: https://github.com/contao/contao/pull/9056
[#9065]: https://github.com/contao/contao/pull/9065
[#9066]: https://github.com/contao/contao/pull/9066
[#9067]: https://github.com/contao/contao/pull/9067
[#9071]: https://github.com/contao/contao/pull/9071
[#9073]: https://github.com/contao/contao/pull/9073
[#9074]: https://github.com/contao/contao/pull/9074
[#9083]: https://github.com/contao/contao/pull/9083
[#9084]: https://github.com/contao/contao/pull/9084
[#9085]: https://github.com/contao/contao/pull/9085
[#9086]: https://github.com/contao/contao/pull/9086
[#9094]: https://github.com/contao/contao/pull/9094
[#9095]: https://github.com/contao/contao/pull/9095
[#9096]: https://github.com/contao/contao/pull/9096
[#9099]: https://github.com/contao/contao/pull/9099
[#9103]: https://github.com/contao/contao/pull/9103
[#9105]: https://github.com/contao/contao/pull/9105
[#9107]: https://github.com/contao/contao/pull/9107
[#9108]: https://github.com/contao/contao/pull/9108
[#9109]: https://github.com/contao/contao/pull/9109
[#9110]: https://github.com/contao/contao/pull/9110
[#9115]: https://github.com/contao/contao/pull/9115
[#9117]: https://github.com/contao/contao/pull/9117
[#9118]: https://github.com/contao/contao/pull/9118
[#9120]: https://github.com/contao/contao/pull/9120
[#9124]: https://github.com/contao/contao/pull/9124
[#9125]: https://github.com/contao/contao/pull/9125
[#9127]: https://github.com/contao/contao/pull/9127
[#9130]: https://github.com/contao/contao/pull/9130
[#9131]: https://github.com/contao/contao/pull/9131
[#9134]: https://github.com/contao/contao/pull/9134
[#9135]: https://github.com/contao/contao/pull/9135
[#9136]: https://github.com/contao/contao/pull/9136
[#9137]: https://github.com/contao/contao/pull/9137
[#9138]: https://github.com/contao/contao/pull/9138
[#9139]: https://github.com/contao/contao/pull/9139
[#9145]: https://github.com/contao/contao/pull/9145
[#9146]: https://github.com/contao/contao/pull/9146
[#9147]: https://github.com/contao/contao/pull/9147
[#9148]: https://github.com/contao/contao/pull/9148
[#9150]: https://github.com/contao/contao/pull/9150
[#9151]: https://github.com/contao/contao/pull/9151
[#9152]: https://github.com/contao/contao/pull/9152
[#9153]: https://github.com/contao/contao/pull/9153
[#9154]: https://github.com/contao/contao/pull/9154
[#9156]: https://github.com/contao/contao/pull/9156
[#9157]: https://github.com/contao/contao/pull/9157
[#9160]: https://github.com/contao/contao/pull/9160
[#9164]: https://github.com/contao/contao/pull/9164
[#9167]: https://github.com/contao/contao/pull/9167
[#9169]: https://github.com/contao/contao/pull/9169
[#9170]: https://github.com/contao/contao/pull/9170
[#9171]: https://github.com/contao/contao/pull/9171
[#9173]: https://github.com/contao/contao/pull/9173
[#9178]: https://github.com/contao/contao/pull/9178
[#9181]: https://github.com/contao/contao/pull/9181
[#9184]: https://github.com/contao/contao/pull/9184
[#9185]: https://github.com/contao/contao/pull/9185
[#9186]: https://github.com/contao/contao/pull/9186
[#9187]: https://github.com/contao/contao/pull/9187
[#9192]: https://github.com/contao/contao/pull/9192
[#9193]: https://github.com/contao/contao/pull/9193
[#9195]: https://github.com/contao/contao/pull/9195
[#9196]: https://github.com/contao/contao/pull/9196
[#9197]: https://github.com/contao/contao/pull/9197
[#9208]: https://github.com/contao/contao/pull/9208
[#9210]: https://github.com/contao/contao/pull/9210
[#9212]: https://github.com/contao/contao/pull/9212
[#9215]: https://github.com/contao/contao/pull/9215
[#9217]: https://github.com/contao/contao/pull/9217
[#9218]: https://github.com/contao/contao/pull/9218
[#9219]: https://github.com/contao/contao/pull/9219
[#9221]: https://github.com/contao/contao/pull/9221
[#9222]: https://github.com/contao/contao/pull/9222
[#9223]: https://github.com/contao/contao/pull/9223
[#9224]: https://github.com/contao/contao/pull/9224
[#9227]: https://github.com/contao/contao/pull/9227
[#9230]: https://github.com/contao/contao/pull/9230
[#9233]: https://github.com/contao/contao/pull/9233
[#9236]: https://github.com/contao/contao/pull/9236
[#9241]: https://github.com/contao/contao/pull/9241
[#9247]: https://github.com/contao/contao/pull/9247
[#9248]: https://github.com/contao/contao/pull/9248
[#9251]: https://github.com/contao/contao/pull/9251
[#9252]: https://github.com/contao/contao/pull/9252
[#9253]: https://github.com/contao/contao/pull/9253
[#9256]: https://github.com/contao/contao/pull/9256
[#9257]: https://github.com/contao/contao/pull/9257
[#9262]: https://github.com/contao/contao/pull/9262
[#9268]: https://github.com/contao/contao/pull/9268
[#9269]: https://github.com/contao/contao/pull/9269
