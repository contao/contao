# Changelog

This project adheres to [Semantic Versioning].

## [5.0.0-RC4] (2022-08-16)

**Fixed issues:**

- [#5141] Don’t use sprintf() for alias URLs ([ausi])
- [#5156] Correctly handle RTL layout and window border in tips ([aschempp])
- [#5158] Do not define ptable for tl_content ([fritzmg])
- [#4326] Fix the remaining relative URLs ([fritzmg])
- [#5139] Add static URLs to data-icon attributes ([ausi])
- [#5147] Fix loop when loading DCA and BackendUser ([ausi])
- [#5144] Fix type error in slug generation ([ausi])
- [#5135] Fix the headline template ([ausi])
- [#5146] Fix Input::isPost() behavior for empty requests ([ausi])
- [#5140] Fix division by zero in _list.html.twig ([ausi])
- [#5065] Rename the database error type ([aschempp])
- [#5117] Change default CSS class names for content elements ([ausi])
- [#5115] Fix member registration issues ([fritzmg])

## [5.0.0-RC3] (2022-08-10)

**New features:**

- [#4682] News feeds as page controller ([bezin])

## [5.0.0-RC2] (2022-08-09)

**New features:**

- [#5103] Use CSS variables in the back end ([leofeyer])
- [#4893] Modern fragments: article teaser + player content elements ([m-vo])
- [#4862] Modern fragments: download content elements ([m-vo])
- [#5017] Use the new features of ACE 1.8 ([leofeyer])
- [#4371] Tune the back end theme ([leofeyer])

**Fixed issues:**

- [#5076] Handle empty array when preloading records ([aschempp])
- [#5060] Do not modify the global TL_LANG array ([ausi])
- [#5097] Allow null query on unique fields and implement early return ([fritzmg])
- [#5048] Fix modulewizard.js ([fritzmg])
- [#4826] Throw correct exceptions on error in DC ([aschempp])
- [#5035] Check DCA config when adding default operations ([aschempp])
- [#4939] Fix sectionwizard.js ([fritzmg])
- [#5036] Don’t cast null values in Model::convertToPhpValue ([ausi])
- [#5093] Fix undefined array key breadcrumb ([ausi])
- [#5094] Fix compatibility with doctrine/dbal 3.3.8 ([ausi])
- [#5070] Fix DcaLoader exceptions ([ausi])
- [#5066] Auto-generate and dump the APP_SECRET during contao-setup ([m-vo])
- [#5088] Show the video URL in the backend preview ([bytehead])
- [#5083] Fix invalid parameter number error ([ausi])
- [#5079] Add the removed count increment again ([ausi])
- [#5073] Fix broken insert tag flags ([ausi])
- [#5038] Remove two redundant body classes ([leofeyer])
- [#5057] Fix empty form uploads causing an error ([fritzmg])
- [#5063] Drop support for MAILER_URL ([aschempp])
- [#5037] Fix a wrong method call in the Newsletter class ([leofeyer])
- [#5023] Correctly handle NDJSON exceptions when checking the DB configuration ([aschempp])
- [#5022] Lazy-load the RememberMeRepository ([aschempp])
- [#5008] Fix useSSL default value ([fritzmg])
- [#5005] Fix database type error in contao:user:create command ([fritzmg])
- [#5000] Fix requirements with Symfony 6.x ([bytehead])
- [#4976] Improve currentRecord checks and permissions ([aschempp])

## [5.0.0-RC1] (2022-07-17)

**New features:**

- [#4834] Validate start/stop date ([aschempp])
- [#4995] Use PHP 8 attributes everywhere ([leofeyer])
- [#4983] Make Contao compatible with Symfony 5.4 and 6.x ([bytehead])
- [#4663] Automatically generate DCA operations with permission checks ([aschempp])
- [#4992] Remove the old app.php entry point ([leofeyer])
- [#4991] Stop using src/Resources ([leofeyer])
- [#4903] Show until/from values for content elements ([ameotoko])
- [#4709] Add a generic voter for editable table fields ([aschempp])
- [#4343] Implement the new Symfony 6.x security interfaces ([bytehead])
- [#4823] Modern fragments: video content elements ([m-vo])
- [#4958] Remove the Contao 4 migrations ([leofeyer])
- [#4935] Remove the install tool ([m-vo])
- [#4915] Modern fragments: template for the markdown element ([m-vo])
- [#4929] Deprecate writing dynamic properties in the DataContainer class ([leofeyer])
- [#4922] Remove $arrCache from the insert tag hooks ([ausi])
- [#4921] Deprecate uppercase letters in insert tags ([ausi])
- [#4920] Keep unknown insert tags as plain strings ([ausi])
- [#4912] Remove the "move" operation from DC_Table ([aschempp])
- [#4918] Move pages and files to the "content" category ([leofeyer])
- [#4797] Change boolean columns from char(1) to tinyint(1) ([fritzmg])
- [#4874] Replace the contao_figure function ([m-vo])
- [#4876] Allow reading from VFS storages in the FigureBuilder ([m-vo])
- [#4875] Improve the `debug:contao-twig` command ([m-vo])
- [#4810] Modern fragments: text content elements (part 2) ([m-vo])
- [#4888] Move the installer into the core ([m-vo])
- [#4892] Use the current record in the `findCurrentPid()` method ([leofeyer])
- [#4770] Permission rework ([Toflar])
- [#4851] Get rid of the base tag ([leofeyer])
- [#4866] Use asset packages for vendor packages ([aschempp])
- [#4775] Modern fragments: link content elements ([m-vo])
- [#4825] Use chevron characters to expand and collapse sections ([leofeyer])
- [#4730] Modern fragments: image and list content elements ([m-vo])
- [#4820] Remove CURRENT_ID constant and session ([ausi])
- [#4800] Cast model values to the correct PHP type ([ausi])
- [#4816] Remove the remaining deprecated stuff ([leofeyer])
- [#4813] Execute the unique check after the save_callback ([leofeyer])
- [#4768] Replace modulewizard with VanillaJS ([aschempp])
- [#4729] Remove deprecated localconfig keys ([ausi])
- [#4761] Use .yaml files everywhere ([leofeyer])
- [#4726] Remove the deprecated request token ([ausi])
- [#4788] Remove the `imagemargin` field ([bezin])
- [#4745] Remove deprecated TL_ROOT constant ([ausi])
- [#4741] Remove deprecated TL_MODE constant ([ausi])
- [#4754] Replace the "edit header" icon with an "edit child elements" icon ([leofeyer])
- [#4773] Remove the news meta fields ([leofeyer])
- [#4772] Remove the wrapper around custom layout sections ([leofeyer])
- [#4749] Add the unfiltered HTML element ([ausi])
- [#4760] Replace `personalData` with `personalDetails` ([leofeyer])
- [#4751] Remove the orderField ([ausi])
- [#4345] Rewrite the Backend.autoFocusFirstInputField to vanilla JS ([aschempp])
- [#4756] Use the same icons for expanding and collapsing everywhere ([leofeyer])
- [#4734] Remove more legacy deprecations ([ausi])
- [#4740] Remove the deprecated FORM_FIELDS feature ([leofeyer])
- [#4746] Remove deprecated TL_SCRIPT constant ([ausi])
- [#4748] Add AVIF to the default image formats ([ausi])
- [#4750] Move assets/images/deferred to var/deferred-images ([ausi])
- [#4742] Allow overwriting the default "read more …" link text ([leofeyer])
- [#4648] Implement news archive permissions as a voter ([Toflar])
- [#4735] Use uppercase country codes for tl_member ([ausi])
- [#4738] Remove some deprecated constants ([ausi])
- [#4737] Remove contao:version command ([ausi])
- [#4721] Remove support for deprecated value in tl_article.printable ([Toflar])
- [#4716] Add two more FilesystemItemIterator related helper functions ([m-vo])
- [#4715] Support our template hierarchy in the Twig "use" tag ([m-vo])
- [#4375] Rewrite fieldset toggling to vanilla JS ([aschempp])
- [#4706] Remove deprecated TL_CRON support ([fritzmg])
- [#4624] Implement sectionWizard in vanilla JS ([fritzmg])
- [#4634] Remove TL_CRON usage from calendar-bundle ([fritzmg])
- [#4635] Remove TL_CRON usage from comments-bundle ([fritzmg])
- [#4671] Modern fragments: text content elements (part 1) ([m-vo])
- [#4642] Remove TL_CRON usage from core-bundle ([fritzmg])
- [#4640] Allow single and forced cron job execution ([fritzmg])
- [#4703] Remove deprecated stuff from AbstractPickerProvider ([bytehead])
- [#4701] Remove hook addLogEntry and its usages ([bytehead])
- [#4664] Add conditional setting/unsetting to the HtmlAttributes class ([m-vo])
- [#4379] Remove the textarea toggleWrap feature ([aschempp])
- [#4651] Remove the deprecated "show to guests only" function ([leofeyer])
- [#4674] Use constants for return values of commands ([m-vo])
- [#4441] Add a bag for file metadata ([m-vo])
- [#4637] Remove TL_CRON usage from newsletter-bundle ([fritzmg])
- [#4636] Remove TL_CRON usage from news-bundle ([fritzmg])
- [#4657] Allow adding document scoped content from within Twig templates ([m-vo])
- [#4658] Add a Twig runtime for code highlighting ([m-vo])
- [#4653] Drop support for an empty `tl_content.ptable` column ([leofeyer])
- [#4655] Remove the deprecated `disableInsertTags` config option ([leofeyer])
- [#4652] Cleanup the response context BC layer ([Toflar])
- [#4650] Remove support for runonce.php files ([Toflar])
- [#4649] Remove the deprecated onrestore_callback ([Toflar])
- [#4495] Add permission checks on global operations ([Toflar])
- [#4582] Remove the getSearchablePages hook ([Toflar])
- [#4073] Replace the getSearchablePages hook in the news bundle ([fritzmg])
- [#4578] Replace the getSearchablePages hook in the FAQ bundle ([Toflar])
- [#4620] Adjust name of Twig marker file that denotes roots of nested template paths ([m-vo])
- [#4522] Add the `Controller::$Template` property again ([leofeyer])
- [#4593] Remove the Backend::getTinyTemplates() method ([de-es])
- [#4579] Support the `|defer` flag in the Template::generateScriptTag() method ([Wusch])
- [#4585] Remove Backend::addFileMetaInformationToRequest() ([Toflar])
- [#4584] Remove the legacy markdown content element ([Toflar])
- [#4576] Adjust the FormTextarea widget according to the @todo comment ([leofeyer])
- [#4564] Remove languages.php and getLanguages hook ([ausi])
- [#4563] Remove countries.php and getCountries hook ([ausi])
- [#4562] Remove TL_CROP ([ausi])
- [#4565] Remove tabindex ([ausi])
- [#4566] Cleanup widget ([ausi])
- [#4510] Rewire the Input and Environment classes to use the Symfony request ([ausi])
- [#4559] Remove the remaining helper files ([leofeyer])
- [#4557] Remove more BC layers ([leofeyer])
- [#4553] Complete more TODOs for Contao 5.0 ([leofeyer])
- [#4554] Remove the BC layers in the .yml files ([leofeyer])
- [#4319] Drop the user agent class and insert tag ([aschempp])
- [#4552] Remove the "fullscreen" option in the back end ([leofeyer])
- [#4548] Replace the "getSearchablePages" hook in the calendar-bundle ([Toflar])
- [#4537] Allow TinyMCE 6 ([leofeyer])
- [#4393] Modern fragment foundation ([m-vo])
- [#4545] Remove the deprecated Model.php stuff ([m-vo])
- [#4544] Remove the deprecated Controller.php stuff ([m-vo])
- [#4453] Rework the input encoding ([ausi])
- [#4543] Remove the deprecated System.php stuff ([m-vo])
- [#4539] Remove the deprecated template stuff ([m-vo])
- [#4541] Remove the deprecated Frontend.php stuff ([m-vo])
- [#4540] Remove the deprecated insert tag stuff ([m-vo])
- [#4536] Allow partial mocks and row()/setRow() when mocking classes with properties ([leofeyer])
- [#4369] Remove deprecated StringUtil stuff ([Toflar])
- [#4531] Remove Contao\Request ([Toflar])
- [#4518] Carry out the planned renaming ([leofeyer])
- [#4178] Remove the "first", "even", "odd" and "last" CSS classes ([fritzmg])
- [#4383] Rewrite tooltips to vanilla JS ([aschempp])
- [#4330] Remove in-memory cache ([Toflar])
- [#4304] Clean up page controllers ([aschempp])
- [#4511] Remove the deprecated textStore widget ([Toflar])
- [#4450] Make replacing insert tags more granular ([m-vo])
- [#4372] Rewrite MetaWizard to vanilla JS ([aschempp])
- [#4367] Remove the orderField support in the picker widgets ([Toflar])
- [#4364] Remove the legacy session access ([Toflar])
- [#4355] Add the SQL import to the backend ([ausi])
- [#3930] Resolve page model in request ([aschempp])
- [#4417] Feature HtmlAttributes in the image studio ([m-vo])
- [#4440] Support sorting and any/all search in FilesystemItemIterator ([m-vo])
- [#4308] Drop database.sql support ([Toflar])
- [#4323] Add the DefaultDcaVoter ([Toflar])
- [#4328] Remove deprecated image methods ([ausi])
- [#4342] Rewrite the Backend.limitPreviewHeight to vanilla JS ([aschempp])
- [#4416] Some small improvements for the HtmlAttribute class ([m-vo])
- [#4381] Allow to json-serialize the HtmlAttributes class ([m-vo])
- [#4368] Remove all sorts of deprecated stuff in the Database namespace ([ausi], [Toflar])
- [#4365] Removed deprecated FrontendCron controller ([Toflar])
- [#4361] Documented missing UPGRADE.md entries ([Toflar])
- [#4362] Use gulp-uglify-es ([aschempp])
- [#3973] Support nested template paths in Twig ([m-vo])
- [#4354] Use PHP8 attributes instead of annotations ([sheeep])
- [#4335] Removed deprecated legacy simple token parsing ([Toflar])
- [#4313] Update the meta files ([leofeyer])
- [#4018] Remove deprecated log_message() ([Toflar])
- [#4344] Handle VFS deprecations and remove BC layers ([m-vo])
- [#4333] Removed deprecated TL_CSS_UNITS superglobal ([Toflar])
- [#4327] Drop deprecated JS stuff ([aschempp])
- [#4332] Remove the article keywords ([Toflar])
- [#4289] Add the missing type hints to our interfaces and abstract classes ([leofeyer])
- [#4316] Drop legacy routing ([aschempp])
- [#3993] Drop the pageSelector and fileSelector widgets ([aschempp])
- [#4306] Drop the initialize.php BC layer ([aschempp])
- [#4317] Drop the `acceptLicense` config option ([aschempp])
- [#4318] Drop `debugMode` leftovers ([aschempp])
- [#4203] Add a HtmlAttributes class and Twig function ([m-vo])
- [#4305] Drop the Google+ remnants ([aschempp])
- [#4314] Remove the Contao 3 class loader ([leofeyer])
- [#4315] Remove the Contao 3 ModuleLoader ([leofeyer])
- [#4291] Remove the first bunch of BC layers ([leofeyer])
- [#4307] Drop the deprecated Encryption library ([Toflar])
- [#4298] Remove the internal CSS editor ([leofeyer])
- [#4290] Do no longer fall back to "web" if "public" does not exist ([leofeyer])

**Fixed issues:**

- [#4997] Make the "twig" service public ([leofeyer])
- [#4994] Always use PHP 8 attributes in the maker bundle ([leofeyer])
- [#4989] Make the Input::post() method compatible with Symfony 6 ([leofeyer])
- [#4984] Always retrieve the session from the request instead of the container ([leofeyer])
- [#4985] Fix the draft view in column mode ([leofeyer])
- [#4977] Fix the pasteinto button if access is denied ([aschempp])
- [#4971] Fix the getCurrentRecord() method ([leofeyer])
- [#4972] Adjust the priority of the DataContainerCallbackListener ([leofeyer])
- [#4938] Always report the installation as being complete ([ausi])
- [#4960] Fix broken subpalette toggling ([ausi])
- [#4947] Add permission checks for paste_buttons ([aschempp])
- [#4940] Fix default callback order ([fritzmg])
- [#4954] Use "website root page" consistently ([leofeyer])
- [#4931] Remove uncached insert tag flag ([ausi])
- [#4934] Document changes to unknown insert tags ([ausi])
- [#4928] Add a template helper method to prefix relative URLs ([leofeyer])
- [#4909] Fix the cache and clientCache values ([leofeyer])
- [#4899] Use `disable=0` instead of `disable!=1` ([leofeyer])
- [#4916] Fix boolean fields always being saved as true ([fritzmg])
- [#4911] Use 0 instead of '' when toggling subpalettes via Ajax ([leofeyer])
- [#4908] Pass the request object to the Environment::phpSelf() method ([leofeyer])
- [#4904] Fix the "click2edit" function ([ameotoko])
- [#4905] Remove redundant type casts ([leofeyer])
- [#4901] Correctly cast the column types ([leofeyer])
- [#4897] Remove InitializeApplicationListener from manager bundle ([rabauss])
- [#4859] Fix the double encoding in DC_File ([leofeyer])
- [#4830] Fix the frontend_user_provider service definition ([leofeyer])
- [#4827] Remove LegacyRoutingException ([aschempp])
- [#4792] Save and submit database records ([aschempp])
- [#4764] Re-add keyboard events for section wizard ([aschempp])
- [#4765] Drop unused toggleAddLanguageButton ([aschempp])
- [#4744] Remove permission check from FrontendIndex::renderPage ([aschempp])
- [#4743] Fix two issues ([leofeyer])
- [#4736] Fix CronTest::testDoesNotRunCronJobIfAlreadyRun ([fritzmg])
- [#4704] Remove DCA view permissions ([aschempp])
- [#4705] Remove leftover callbacks ([ausi])
- [#4348] Fix an execute statement ([ausi])
- [#4677] Drop the "loadNavigation" Ajax action ([aschempp])
- [#4675] Undeprecate the third Input::get() parameter ([ausi])
- [#4666] tl_content.ptable is mandatory now ([Toflar])
- [#4661] Add missing try catch blocks ([Toflar])
- [#4613] Fix access of global DropZone class ([Toflar])
- [#4598] Remove leftover contao.encryption_key occurences ([bytehead])
- [#4589] Re-introduce autowiring aliases for subscribed services ([fritzmg])
- [#4587] Add missing service argument for Version410Update ([fritzmg])
- [#4555] Correctly build Twig logical name from fragment template name ([m-vo])
- [#4517] Fix miscellaneous minor issues ([leofeyer])
- [#4446] Support nested paths when generating Twig IDE autocompletion file ([m-vo])
- [#4438] Fix several errors that now appear due to removed BC layers ([m-vo])
- [#4377] Require Flysystem 3 only ([m-vo])
- [#4360] Templates should end with an empty line ([leofeyer])
- [#4358] Fix code style for InsertTags::executeReplace ([fritzmg])
- [#4338] Remove the article keyword leftovers ([leofeyer])
- [#4325] Stop prefixing the DC driver ([bytehead])
- [#4337] Use the FQCN in the newsletter DCA ([leofeyer])
- [#4190] Remove left-over url field in tl_module ([bytehead])

[Semantic Versioning]: https://semver.org/spec/v2.0.0.html
[5.0.0-RC4]: https://github.com/contao/contao/releases/tag/5.0.0-RC4
[5.0.0-RC3]: https://github.com/contao/contao/releases/tag/5.0.0-RC3
[5.0.0-RC2]: https://github.com/contao/contao/releases/tag/5.0.0-RC2
[5.0.0-RC1]: https://github.com/contao/contao/releases/tag/5.0.0-RC1
[ameotoko]: https://github.com/ameotoko
[aschempp]: https://github.com/aschempp
[ausi]: https://github.com/ausi
[bezin]: https://github.com/bezin
[bytehead]: https://github.com/bytehead
[de-es]: https://github.com/de-es
[fritzmg]: https://github.com/fritzmg
[leofeyer]: https://github.com/leofeyer
[m-vo]: https://github.com/m-vo
[rabauss]: https://github.com/rabauss
[sheeep]: https://github.com/sheeep
[Toflar]: https://github.com/Toflar
[Wusch]: https://github.com/Wusch
[#5141]: https://github.com/contao/contao/pull/5141
[#5156]: https://github.com/contao/contao/pull/5156
[#5158]: https://github.com/contao/contao/pull/5158
[#4326]: https://github.com/contao/contao/pull/4326
[#5139]: https://github.com/contao/contao/pull/5139
[#5147]: https://github.com/contao/contao/pull/5147
[#5144]: https://github.com/contao/contao/pull/5144
[#5135]: https://github.com/contao/contao/pull/5135
[#5146]: https://github.com/contao/contao/pull/5146
[#5140]: https://github.com/contao/contao/pull/5140
[#5065]: https://github.com/contao/contao/pull/5065
[#5117]: https://github.com/contao/contao/pull/5117
[#5115]: https://github.com/contao/contao/pull/5115
[#4682]: https://github.com/contao/contao/pull/4682
[#5103]: https://github.com/contao/contao/pull/5103
[#4893]: https://github.com/contao/contao/pull/4893
[#4862]: https://github.com/contao/contao/pull/4862
[#5017]: https://github.com/contao/contao/pull/5017
[#4371]: https://github.com/contao/contao/pull/4371
[#5076]: https://github.com/contao/contao/pull/5076
[#5060]: https://github.com/contao/contao/pull/5060
[#5097]: https://github.com/contao/contao/pull/5097
[#5048]: https://github.com/contao/contao/pull/5048
[#4826]: https://github.com/contao/contao/pull/4826
[#5035]: https://github.com/contao/contao/pull/5035
[#4939]: https://github.com/contao/contao/pull/4939
[#5036]: https://github.com/contao/contao/pull/5036
[#5093]: https://github.com/contao/contao/pull/5093
[#5094]: https://github.com/contao/contao/pull/5094
[#5070]: https://github.com/contao/contao/pull/5070
[#5066]: https://github.com/contao/contao/pull/5066
[#5088]: https://github.com/contao/contao/pull/5088
[#5083]: https://github.com/contao/contao/pull/5083
[#5079]: https://github.com/contao/contao/pull/5079
[#5073]: https://github.com/contao/contao/pull/5073
[#5038]: https://github.com/contao/contao/pull/5038
[#5057]: https://github.com/contao/contao/pull/5057
[#5063]: https://github.com/contao/contao/pull/5063
[#5037]: https://github.com/contao/contao/pull/5037
[#5023]: https://github.com/contao/contao/pull/5023
[#5022]: https://github.com/contao/contao/pull/5022
[#5008]: https://github.com/contao/contao/pull/5008
[#5005]: https://github.com/contao/contao/pull/5005
[#5000]: https://github.com/contao/contao/pull/5000
[#4976]: https://github.com/contao/contao/pull/4976
[#4834]: https://github.com/contao/contao/pull/4834
[#4995]: https://github.com/contao/contao/pull/4995
[#4983]: https://github.com/contao/contao/pull/4983
[#4663]: https://github.com/contao/contao/pull/4663
[#4992]: https://github.com/contao/contao/pull/4992
[#4991]: https://github.com/contao/contao/pull/4991
[#4903]: https://github.com/contao/contao/pull/4903
[#4709]: https://github.com/contao/contao/pull/4709
[#4343]: https://github.com/contao/contao/pull/4343
[#4823]: https://github.com/contao/contao/pull/4823
[#4958]: https://github.com/contao/contao/pull/4958
[#4935]: https://github.com/contao/contao/pull/4935
[#4915]: https://github.com/contao/contao/pull/4915
[#4929]: https://github.com/contao/contao/pull/4929
[#4922]: https://github.com/contao/contao/pull/4922
[#4921]: https://github.com/contao/contao/pull/4921
[#4920]: https://github.com/contao/contao/pull/4920
[#4912]: https://github.com/contao/contao/pull/4912
[#4918]: https://github.com/contao/contao/pull/4918
[#4797]: https://github.com/contao/contao/pull/4797
[#4874]: https://github.com/contao/contao/pull/4874
[#4876]: https://github.com/contao/contao/pull/4876
[#4875]: https://github.com/contao/contao/pull/4875
[#4810]: https://github.com/contao/contao/pull/4810
[#4888]: https://github.com/contao/contao/pull/4888
[#4892]: https://github.com/contao/contao/pull/4892
[#4770]: https://github.com/contao/contao/pull/4770
[#4851]: https://github.com/contao/contao/pull/4851
[#4866]: https://github.com/contao/contao/pull/4866
[#4775]: https://github.com/contao/contao/pull/4775
[#4825]: https://github.com/contao/contao/pull/4825
[#4730]: https://github.com/contao/contao/pull/4730
[#4820]: https://github.com/contao/contao/pull/4820
[#4800]: https://github.com/contao/contao/pull/4800
[#4816]: https://github.com/contao/contao/pull/4816
[#4813]: https://github.com/contao/contao/pull/4813
[#4768]: https://github.com/contao/contao/pull/4768
[#4729]: https://github.com/contao/contao/pull/4729
[#4761]: https://github.com/contao/contao/pull/4761
[#4726]: https://github.com/contao/contao/pull/4726
[#4788]: https://github.com/contao/contao/pull/4788
[#4745]: https://github.com/contao/contao/pull/4745
[#4741]: https://github.com/contao/contao/pull/4741
[#4754]: https://github.com/contao/contao/pull/4754
[#4773]: https://github.com/contao/contao/pull/4773
[#4772]: https://github.com/contao/contao/pull/4772
[#4749]: https://github.com/contao/contao/pull/4749
[#4760]: https://github.com/contao/contao/pull/4760
[#4751]: https://github.com/contao/contao/pull/4751
[#4345]: https://github.com/contao/contao/pull/4345
[#4756]: https://github.com/contao/contao/pull/4756
[#4734]: https://github.com/contao/contao/pull/4734
[#4740]: https://github.com/contao/contao/pull/4740
[#4746]: https://github.com/contao/contao/pull/4746
[#4748]: https://github.com/contao/contao/pull/4748
[#4750]: https://github.com/contao/contao/pull/4750
[#4742]: https://github.com/contao/contao/pull/4742
[#4648]: https://github.com/contao/contao/pull/4648
[#4735]: https://github.com/contao/contao/pull/4735
[#4738]: https://github.com/contao/contao/pull/4738
[#4737]: https://github.com/contao/contao/pull/4737
[#4721]: https://github.com/contao/contao/pull/4721
[#4716]: https://github.com/contao/contao/pull/4716
[#4715]: https://github.com/contao/contao/pull/4715
[#4375]: https://github.com/contao/contao/pull/4375
[#4706]: https://github.com/contao/contao/pull/4706
[#4624]: https://github.com/contao/contao/pull/4624
[#4634]: https://github.com/contao/contao/pull/4634
[#4635]: https://github.com/contao/contao/pull/4635
[#4671]: https://github.com/contao/contao/pull/4671
[#4642]: https://github.com/contao/contao/pull/4642
[#4640]: https://github.com/contao/contao/pull/4640
[#4703]: https://github.com/contao/contao/pull/4703
[#4701]: https://github.com/contao/contao/pull/4701
[#4664]: https://github.com/contao/contao/pull/4664
[#4379]: https://github.com/contao/contao/pull/4379
[#4651]: https://github.com/contao/contao/pull/4651
[#4674]: https://github.com/contao/contao/pull/4674
[#4441]: https://github.com/contao/contao/pull/4441
[#4637]: https://github.com/contao/contao/pull/4637
[#4636]: https://github.com/contao/contao/pull/4636
[#4657]: https://github.com/contao/contao/pull/4657
[#4658]: https://github.com/contao/contao/pull/4658
[#4653]: https://github.com/contao/contao/pull/4653
[#4655]: https://github.com/contao/contao/pull/4655
[#4652]: https://github.com/contao/contao/pull/4652
[#4650]: https://github.com/contao/contao/pull/4650
[#4649]: https://github.com/contao/contao/pull/4649
[#4495]: https://github.com/contao/contao/pull/4495
[#4582]: https://github.com/contao/contao/pull/4582
[#4073]: https://github.com/contao/contao/pull/4073
[#4578]: https://github.com/contao/contao/pull/4578
[#4620]: https://github.com/contao/contao/pull/4620
[#4522]: https://github.com/contao/contao/pull/4522
[#4593]: https://github.com/contao/contao/pull/4593
[#4579]: https://github.com/contao/contao/pull/4579
[#4585]: https://github.com/contao/contao/pull/4585
[#4584]: https://github.com/contao/contao/pull/4584
[#4576]: https://github.com/contao/contao/pull/4576
[#4564]: https://github.com/contao/contao/pull/4564
[#4563]: https://github.com/contao/contao/pull/4563
[#4562]: https://github.com/contao/contao/pull/4562
[#4565]: https://github.com/contao/contao/pull/4565
[#4566]: https://github.com/contao/contao/pull/4566
[#4510]: https://github.com/contao/contao/pull/4510
[#4559]: https://github.com/contao/contao/pull/4559
[#4557]: https://github.com/contao/contao/pull/4557
[#4553]: https://github.com/contao/contao/pull/4553
[#4554]: https://github.com/contao/contao/pull/4554
[#4319]: https://github.com/contao/contao/pull/4319
[#4552]: https://github.com/contao/contao/pull/4552
[#4548]: https://github.com/contao/contao/pull/4548
[#4537]: https://github.com/contao/contao/pull/4537
[#4393]: https://github.com/contao/contao/pull/4393
[#4545]: https://github.com/contao/contao/pull/4545
[#4544]: https://github.com/contao/contao/pull/4544
[#4453]: https://github.com/contao/contao/pull/4453
[#4543]: https://github.com/contao/contao/pull/4543
[#4539]: https://github.com/contao/contao/pull/4539
[#4541]: https://github.com/contao/contao/pull/4541
[#4540]: https://github.com/contao/contao/pull/4540
[#4536]: https://github.com/contao/contao/pull/4536
[#4369]: https://github.com/contao/contao/pull/4369
[#4531]: https://github.com/contao/contao/pull/4531
[#4518]: https://github.com/contao/contao/pull/4518
[#4178]: https://github.com/contao/contao/pull/4178
[#4383]: https://github.com/contao/contao/pull/4383
[#4330]: https://github.com/contao/contao/pull/4330
[#4304]: https://github.com/contao/contao/pull/4304
[#4511]: https://github.com/contao/contao/pull/4511
[#4450]: https://github.com/contao/contao/pull/4450
[#4372]: https://github.com/contao/contao/pull/4372
[#4367]: https://github.com/contao/contao/pull/4367
[#4364]: https://github.com/contao/contao/pull/4364
[#4355]: https://github.com/contao/contao/pull/4355
[#3930]: https://github.com/contao/contao/pull/3930
[#4417]: https://github.com/contao/contao/pull/4417
[#4440]: https://github.com/contao/contao/pull/4440
[#4308]: https://github.com/contao/contao/pull/4308
[#4323]: https://github.com/contao/contao/pull/4323
[#4328]: https://github.com/contao/contao/pull/4328
[#4342]: https://github.com/contao/contao/pull/4342
[#4416]: https://github.com/contao/contao/pull/4416
[#4381]: https://github.com/contao/contao/pull/4381
[#4368]: https://github.com/contao/contao/pull/4368
[#4365]: https://github.com/contao/contao/pull/4365
[#4361]: https://github.com/contao/contao/pull/4361
[#4362]: https://github.com/contao/contao/pull/4362
[#3973]: https://github.com/contao/contao/pull/3973
[#4354]: https://github.com/contao/contao/pull/4354
[#4335]: https://github.com/contao/contao/pull/4335
[#4313]: https://github.com/contao/contao/pull/4313
[#4018]: https://github.com/contao/contao/pull/4018
[#4344]: https://github.com/contao/contao/pull/4344
[#4333]: https://github.com/contao/contao/pull/4333
[#4327]: https://github.com/contao/contao/pull/4327
[#4332]: https://github.com/contao/contao/pull/4332
[#4289]: https://github.com/contao/contao/pull/4289
[#4316]: https://github.com/contao/contao/pull/4316
[#3993]: https://github.com/contao/contao/pull/3993
[#4306]: https://github.com/contao/contao/pull/4306
[#4317]: https://github.com/contao/contao/pull/4317
[#4318]: https://github.com/contao/contao/pull/4318
[#4203]: https://github.com/contao/contao/pull/4203
[#4305]: https://github.com/contao/contao/pull/4305
[#4314]: https://github.com/contao/contao/pull/4314
[#4315]: https://github.com/contao/contao/pull/4315
[#4291]: https://github.com/contao/contao/pull/4291
[#4307]: https://github.com/contao/contao/pull/4307
[#4298]: https://github.com/contao/contao/pull/4298
[#4290]: https://github.com/contao/contao/pull/4290
[#4997]: https://github.com/contao/contao/pull/4997
[#4994]: https://github.com/contao/contao/pull/4994
[#4989]: https://github.com/contao/contao/pull/4989
[#4984]: https://github.com/contao/contao/pull/4984
[#4985]: https://github.com/contao/contao/pull/4985
[#4977]: https://github.com/contao/contao/pull/4977
[#4971]: https://github.com/contao/contao/pull/4971
[#4972]: https://github.com/contao/contao/pull/4972
[#4938]: https://github.com/contao/contao/pull/4938
[#4960]: https://github.com/contao/contao/pull/4960
[#4947]: https://github.com/contao/contao/pull/4947
[#4940]: https://github.com/contao/contao/pull/4940
[#4954]: https://github.com/contao/contao/pull/4954
[#4931]: https://github.com/contao/contao/pull/4931
[#4934]: https://github.com/contao/contao/pull/4934
[#4928]: https://github.com/contao/contao/pull/4928
[#4909]: https://github.com/contao/contao/pull/4909
[#4899]: https://github.com/contao/contao/pull/4899
[#4916]: https://github.com/contao/contao/pull/4916
[#4911]: https://github.com/contao/contao/pull/4911
[#4908]: https://github.com/contao/contao/pull/4908
[#4904]: https://github.com/contao/contao/pull/4904
[#4905]: https://github.com/contao/contao/pull/4905
[#4901]: https://github.com/contao/contao/pull/4901
[#4897]: https://github.com/contao/contao/pull/4897
[#4859]: https://github.com/contao/contao/pull/4859
[#4830]: https://github.com/contao/contao/pull/4830
[#4827]: https://github.com/contao/contao/pull/4827
[#4792]: https://github.com/contao/contao/pull/4792
[#4764]: https://github.com/contao/contao/pull/4764
[#4765]: https://github.com/contao/contao/pull/4765
[#4744]: https://github.com/contao/contao/pull/4744
[#4743]: https://github.com/contao/contao/pull/4743
[#4736]: https://github.com/contao/contao/pull/4736
[#4704]: https://github.com/contao/contao/pull/4704
[#4705]: https://github.com/contao/contao/pull/4705
[#4348]: https://github.com/contao/contao/pull/4348
[#4677]: https://github.com/contao/contao/pull/4677
[#4675]: https://github.com/contao/contao/pull/4675
[#4666]: https://github.com/contao/contao/pull/4666
[#4661]: https://github.com/contao/contao/pull/4661
[#4613]: https://github.com/contao/contao/pull/4613
[#4598]: https://github.com/contao/contao/pull/4598
[#4589]: https://github.com/contao/contao/pull/4589
[#4587]: https://github.com/contao/contao/pull/4587
[#4555]: https://github.com/contao/contao/pull/4555
[#4517]: https://github.com/contao/contao/pull/4517
[#4446]: https://github.com/contao/contao/pull/4446
[#4438]: https://github.com/contao/contao/pull/4438
[#4377]: https://github.com/contao/contao/pull/4377
[#4360]: https://github.com/contao/contao/pull/4360
[#4358]: https://github.com/contao/contao/pull/4358
[#4338]: https://github.com/contao/contao/pull/4338
[#4325]: https://github.com/contao/contao/pull/4325
[#4337]: https://github.com/contao/contao/pull/4337
[#4190]: https://github.com/contao/contao/pull/4190
