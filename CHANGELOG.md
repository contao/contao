# Changelog

This project adheres to [Semantic Versioning].

## [4.13.9] (2022-09-15)

**Fixed issues:**

- [#5277] Fix the order of the palette combiner ([ausi])
- [#5269] Don't return empty theme directories in the TemplateLocator ([m-vo])
- [#5268] Fix page mounts not being applied correctly ([Toflar])
- [#5260] Fix a PHP8 issue if the confirmation key is not translated ([aschempp])
- [#5249] Fix cache tagging for aliased content elements ([fritzmg])
- [#5240] Improve the order of operations during the Contao setup ([m-vo])
- [#5234] Correctly list the templates in "override all" mode ([leofeyer])
- [#5227] Fix the password field icon ([leofeyer])
- [#5222] Use the firewall name instead of the scope to determine the access strategy ([aschempp])
- [#5221] Correctly validate same page alias with required parameters on multiple domains ([aschempp])
- [#5206] Deprecate the article-to-PDF functionality ([aschempp])
- [#5194] Deprecate the Controller::setStaticUrls() method ([leofeyer])

## [4.13.8] (2022-08-17)

**Fixed issues:**

- [#5185] Make the maker-bundle compatible with symfony/maker-bundle >= 1.44.0 ([leofeyer])
- [#4571] Allow searching content elements and members by ID ([christianbarkowsky])
- [#5099] Fix the preview link purge job ([aschempp])
- [#5167] Allow usages of picker without active record ([bezin])
- [#5170] Correctly replace insert tags within links in the markdown element ([Toflar])
- [#5165] Add the password toggle to the "change password" dialog ([leofeyer])
- [#5137] Set login constants in request listener ([fritzmg])
- [#5159] Do not define ptable for tl_content ([fritzmg])

## [4.13.7] (2022-08-15)

**Fixed issues:**

- [#5112] Allow subpalettes based on value 0 in case of selects ([dennisbohn])
- [#5142] Support pid foreign keys in DC_Table ([richardhj])
- [#5104] Skip the database backup in contao:migrate command if there is no work to do ([qzminski])
- [#5092] Check database version in migrate command ([ausi])
- [#4979] Fix legacy routing matcher not matching the route if page has no alias ([qzminski])
- [#5064] Deprecate the MAILER_URL environment variable ([aschempp])
- [#4988] Redirect to fragment URL on preview URL error ([aschempp])
- [#5009] Keep the ResponseContextAccessor available for autowiring ([aschempp])
- [#5061] Improve canonical URL help text ([ausi])
- [#5129] Handle non-existing table in DcaExtractor ([aschempp])
- [#5108] Do not override manually defined ptable configuration ([dmolineus])
- [#5016] Fix several argument warnings on PHP methods ([aschempp])
- [#5095] Don’t use deprecated getIdentifierQuoteCharacter() ([ausi])
- [#5098] Fix compatibility with doctrine/dbal 3.3.8 ([ausi])
- [#5071] Deprecate noCache parameter of DcaLoader ([ausi])
- [#5059] Fix illegal string offsets in the translator ([ausi])
- [#5026] Also set collation for database.sql files ([fritzmg])
- [#5020] Fix wrong UUID being applied when moving resources ([m-vo])
- [#4965] Use cache_suffix for TinyMCE ([fritzmg])
- [#4973] Deprecate the importUser hook ([bytehead])
- [#4957] Fixed str_replace errors when passing null ([aschempp])
- [#4961] Always set both collate and collation to the same value ([fritzmg])
- [#4952] Allow iterating FilesystemItemIterator multiple times ([m-vo])
- [#4933] Do not set header in Ajax::executePostActions ([fritzmg])
- [#4948] Fix potential PHP 8 warnings when resizing an uploaded image ([qzminski])

## [4.13.6] (2022-07-05)

**Fixed issues:**

- [#4941] Reduce System::getContainer and $container->getParameter calls ([fritzmg])
- [#4932] Deprecate insert tag flag uncached ([ausi])
- [#4808] Various PHP 8 fixes ([aschempp])
- [#4945] Hard-code timezone to avoid time deviation ([bezin])
- [#4926] Allow to exclude all tl_page fields ([aschempp])
- [#4870] Disable widgets for configured settings ([aschempp])
- [#4878] Make sure `eval.rte` is a string ([fritzmg])
- [#4891] Fix the query string being lost during the preview script redirect ([qzminski])
- [#4889] Fix a potential PHP 8 warning in tl_article callback ([qzminski])
- [#4881] Use the resource finder in the lightbox migration ([leofeyer])
- [#4883] Hide the "icon" table header in the user and member module ([leofeyer])
- [#4886] Always unlock search tables to prevent deadlocks ([ausi])
- [#4877] Fix a potential PHP 8 warning in the Search class ([qzminski])
- [#4848] Fix a few potential PHP 8 warnings ([qzminski])
- [#4850] Show array keys als fallback for sorting dropdowns ([Tastaturberuf])
- [#4861] Fix a potential version comparison if the field definitions are missing ([qzminski])
- [#4857] Correctly add the preview script ([leofeyer])
- [#4849] Show array keys instead of nothing in show column mode ([Tastaturberuf])
- [#4833] Order the languages in the meta wizard ([leofeyer])
- [#4778] Fix undefined array key while button generation ([rabauss])
- [#4838] Improve DropSearchMigration ([fritzmg])
- [#4836] Fix infinite loop while loading of countries ([rabauss])
- [#4807] Fix date filtering in DC_Table ([fritzmg])
- [#4794] Deprecate Controller::generateMargin ([bezin])
- [#4786] Do not allow empty values for the badge title and custom CSS/JS scripts ([leofeyer])
- [#4782] Add a cache timeout for the `{{date::Y}}` insert tag ([Toflar])
- [#4799] Always set both collate and collation to the same value ([fritzmg])

## [4.13.5] (2022-06-03)

**New features:**

- [#3924] Show page name in duplicate alias error ([aschempp])
- [#4665] Make the template element more flexible ([doishub])
- [#4672] Add a deprecation helper to detect insert tags in Twig templates ([m-vo])

**Fixed issues:**

- [#4785] Fix the while loop in the `Controller::getParentEntries()` method ([leofeyer])
- [#4784] Return 0 after deleting a deferred image reference ([leofeyer])
- [#4757] Fix a potential PHP 8 warning in booknav frontend module ([qzminski])
- [#4763] Correctly toggle checkbox groups with collapseUncheckedGroups ([aschempp])
- [#4774] Fix a potential PHP 8 error in the Contao\Environment class ([qzminski])
- [#4767] Fix a potential PHP 8 warning in the Contao\Controller class ([qzminski])
- [#4679] Fix several accessibility issues in the back end navigation ([aschempp])
- [#4752] Deprecate orderField ([ausi])
- [#4732] Fix a potential PHP 8 warning in the sitemap module ([qzminski])
- [#4747] Apply the rel=lightbox migration to all rte fields ([ausi])
- [#4728] Fix DBAFS when upload_path contains subfolders ([fritzmg])
- [#4733] Undeprecate reload…tree ajax post actions ([ausi])
- [#4739] Make sure page language is always a string in routing ([aschempp])
- [#4720] Fix a potential PHP 8 warning in the breadcrumb module ([qzminski])
- [#4719] Fix the PHP 8 warning if a $_SERVER variable does not exist ([qzminski])
- [#4713] Also override Return-Path and Sender address ([fritzmg])
- [#4717] Correctly handle index pages without URL prefix ([aschempp])
- [#4692] Harden against invalid input ([leofeyer])
- [#4691] Fix failing backup on contao:migrate must abort the command ([Toflar])
- [#4683] Drop the DBAFS file size limit ([m-vo])
- [#4673] Fix undefined array key warning when using an article list ([MarkejN])
- [#4662] Deprecate the move operation ([aschempp])
- [#4443] Expose public URIs in the VFS ([m-vo])
- [#4681] Fix the PHP 8 warning in Contao\Date class ([qzminski])
- [#4669] Remove all "Unable to generate URL for page" log entries ([leofeyer])
- [#4668] Fix the hasText/hasDetails usage ([leofeyer])
- [#4667] Stop using the deprecated VERSION constant ([bezin])
- [#4656] Unset TL_CONFIG in ContaoTestCase::tearDown() ([fritzmg])
- [#4643] Remove superfluous class name in deprecation messages ([fritzmg])
- [#4632] Undeprecate some autowiring aliases ([fritzmg])
- [#4641] Fix missing PurgePreviewLinksCron registration ([fritzmg])
- [#4623] Improve how the Contao Twig escaper works ([m-vo])
- [#4617] Ensure that the license field in the MetaWizard contains a URL ([Toflar])
- [#4592] Support both `collation:` and `collate:` ([leofeyer])
- [#4631] Fix the empty URL check in the getCandidates() method ([leofeyer])
- [#4627] Fix RelLightboxMigration if ContaoCommentsBundle is not installed ([fritzmg])
- [#4608] Deprecate the Backend::getTinyTemplates() method ([de-es])

## [4.13.4] (2022-05-05)

**New features:**

- [#4329] Deprecated Cache lib ([Toflar])
- [#4506] Disable the TinyMCE context menu by default ([de-es])

**Fixed issues:**

- [#4504] Improve the XDebug experience ([m-vo])
- [#4514] Do not use head and attribute data in search context ([aschempp])
- [#4603] Correctly generate the edit URL of a version ([leofeyer])
- [#4396] Deprecate the StringUtil::toHtml5() method ([m-vo])
- [#4604] Use host name without port in some controllers ([bezin])
- [#4601] Correctly toggle the CSS class when (un)publishing a format definition ([leofeyer])
- [#4600] Fix the DCA picker in the CSS editor ([leofeyer])
- [#4599] Do not throw an exception if a page insert tag cannot be generated ([leofeyer])
- [#3995] Replace old back end paths ([aschempp])
- [#4501] Fix undefined sorting mode for group header ([rabauss])
- [#4489] Fix the SendNewsletterEvent when sending as text only ([fritzmg])
- [#4573] Add loop as an option to YouTube videos ([Wusch])
- [#4567] Deprecate Controller::getSpellcheckerString() ([ausi])
- [#4523] Handle predefined image sizes when validating the ImageSize widget ([qzminski])
- [#4549] Fix a type error in the listing module ([Toflar])
- [#4534] Fix the FAQ page module throwing a warning in PHP 8 if author could not be fetched ([qzminski])
- [#4535] Fix the registration module throwing a warning in PHP 8 if captcha is disabled ([qzminski])
- [#4533] Fix an error when unpacking an associative array of model search criteria values ([qzminski])
- [#4527] Deprecate Contao\Request ([Toflar])
- [#4521] Fix the translation domain in the root page dependent select ([leofeyer])
- [#4456] Fix the record preview ([bezin])
- [#4437] Correctly resolve parameters when prepending bundle config ([aschempp])
- [#4451] Quote all schema names, same as we do for inserts ([ausi])
- [#4448] Skip row size calculation for MyISAM ([ausi])
- [#4447] Fix simple token parser default value for unknown variables ([m-vo])

## [4.13.3] (2022-05-05)

**Security fixes:**

- Prevent XSS via canonical tags in the front end ([CVE-2022-24899])

## [4.13.2] (2022-03-31)

**Fixed issues:**

- [#4431] Allow to purge the preview cache in the user profile ([leofeyer])
- [#4433] Always create an article if page has no layout ([aschempp])
- [#4426] Add the service subscriber tag to the correct controller ([m-vo])
- [#4303] Move the logic from LogoutHandler to LogoutSuccessListener ([bytehead])
- [#4301] Remove file title from sources element ([CMSworker])
- [#4425] Return the prefix-relative path when getting filesystem items from the VFS ([m-vo])
- [#4410] Use symfony/polyfill-intl-idn instead of true/punycode ([leofeyer])
- [#4179] Add a warning for too large database row sizes ([ausi])
- [#4297] Fix requireItem sorting ([aschempp])
- [#4346] Drop useless framework initialization ([m-vo])
- [#4397] Fix several VFS bugs ([m-vo])
- [#4398] Add missing annotations in ContentModel for showPreview ([m-vo])
- [#4311] Remove nullable response in controllers ([aschempp])
- [#4353] Ensure the decorated access decision manager shows up in profiler ([Toflar])
- [#4302] Always render protected pages in the pretty error screen listener ([aschempp])
- [#4376] Increase the speed of the functional tests ([ausi])
- [#4374] Fix minor typo in InsertTags ([fritzmg])
- [#4359] Fix code style for InsertTags::executeReplace ([fritzmg])
- [#4300] Fix symlink tests on Windows ([m-vo])
- [#4287] Fix the route sorting in the Route404Provider ([leofeyer])
- [#4288] Revert an accidental change in the cal_ templates ([leofeyer])
- [#4292] Ignore file symlinks when auto-mounting adapters ([m-vo])

## [4.13.1] (2022-03-15)

**Fixed issues:**

- [#4026] Fix multiple page controller routing issues ([aschempp])
- [#4281] Add missing isSortable checks to the picker widget ([MarkejN])
- [#4279] Fix bug with database query returing non-string types ([ausi])
- [#4272] Add a help wizard if the canonical URL fields are disabled ([leofeyer])
- [#4273] Adjust the "Recreate the XML files" description ([leofeyer])
- [#4270] Only shorten the main headline elements if necessary ([leofeyer])
- [#4275] Fix a potential PHP 8 incompatibility when generating a DCA column ([qzminski])
- [#4274] Fall back to the section key if there is no label ([leofeyer])
- [#4271] Correctly show all breadcrumb items ([leofeyer])
- [#4197] Fix some dynamic routes handling ([aschempp])
- [#4269] Use the correct web dir in the InstallWebDirCommand ([leofeyer])
- [#4268] Fix the type hint of the MessageCatalogue::isContaoDomain() method ([leofeyer])
- [#4267] Fix two minor issues in the install tool ([leofeyer])
- [#4158] Set DB server version in install tool ([ausi])
- [#4228] Improve the performance of contao:backup:create ([Toflar])
- [#4261] Fix SQL error in purge expired data cron ([ausi])
- [#4262] Fix SQL commands not supported in prepared statements ([ausi])
- [#4264] Make search accent insensitive ([ausi])
- [#4254] Fix infinite loop while loading of languages ([rabauss])
- [#4202] Fix the remaining image size labels ([fritzmg])
- [#4265] Use service_closure instead of lazy service ([ausi])
- [#4259] Avoid error if the DATABASE_URL environment variable is an empty string ([qzminski])
- [#4245] Decode equal sign when parsing query parameters of figure insert tag ([m-vo])
- [#4244] Make sure tl_content.type has an index ([Toflar])
- [#4216] Skip non-UTF-8 resources when syncing the DBAFS ([m-vo])
- [#4230] Fix undefined array index warnings for content elements and forms ([fritzmg])
- [#4224] Execute BackendTemplate#compile() when using the AbstractBackendController ([m-vo])
- [#4221] Fix the FigureRendererTest ([aschempp])
- [#4208] Lower max file size in Dbafs service ([m-vo])
- [#4183] Clarify the backup command description ([Mynyx])
- [#4162] Fix the widget height ([leofeyer])

## [4.13.0] (2022-02-17)

**New features:**

- [#4123] Add a link to the Contao manual in the back end ([MDevster])

**Fixed issues:**

- [#4151] Make the `crontao.cron` service lazy ([aschempp])
- [#4149] Use static description for commands ([m-vo])
- [#4133] Improve the preview links back end ([aschempp])
- [#4141] Support symlinks in the upload directory ([m-vo])
- [#4145] Fix time sensitive tests ([ausi])
- [#4126] Check return type of generateLabelRecord method ([bezin])
- [#4143] Do not use transactions for restoring backups ([ausi])
- [#4139] Adjust labels for root page dependent modules ([bytehead])
- [#4121] Show custom Twig templates in the back end dropdowns ([m-vo])
- [#4140] Add feed image size property doc comment ([bezin])
- [#4136] Increase the minimum version of the Composer runtime API ([dmolineus])
- [#4117] Do not add the element name to the PHP attribute in the maker bundle ([leofeyer])
- [#4134] Remove custom template option ([bytehead])

## [4.13.0-RC3] (2022-02-11)

**New features:**

- [#3990] Fast manual file sync for the back end ([m-vo])
- [#4004] Support virtual filesystem in CLI backup management ([Toflar])
- [#4042] Enable SQL strict mode by default ([m-vo])

**Fixed issues:**

- [#4099] Do not store record preview for DC_Folder instances ([bezin])
- [#4114] Allow DCAs without driver ([leofeyer])
- [#4113] Return an empty string if there is no driver ([leofeyer])
- [#4112] Skip all dot files when syncing the DBAFS ([m-vo])
- [#4103] Fix the color of bold strings inside error messages ([leofeyer])
- [#3992] Automatically generate Twig IDE auto-completion mappings ([m-vo])
- [#4096] Fix an undefiend array key ([richardhj])
- [#4065] Fix order of parameters in AsContentElement and AsFrontendModule constructors ([m-vo])
- [#4078] Fix 'Purge the preview cache' (path not found) ([AlexanderWillner])
- [#4095] Fix the logger service calls ([SeverinGloeckle])
- [#4094] Fix missing fallback for densities in preview factory ([m-vo])
- [#4093] Allow autowiring of preview factory ([m-vo])
- [#4074] Fix `contao:user:list` with empty database ([AlexanderWillner])

## [4.13.0-RC2] (2022-02-08)

**New features:**

- [#4012] Allow filtering for files/directories when listing contents ([m-vo])

**Fixed issues:**

- [#4052] Do not fetch similar pages with empty alias ([aschempp])
- [#4046] Encode binary data as hex literal in backup dump ([ausi])
- [#3994] Pre-render record preview for undo view on delete ([bezin])
- [#4057] Limit image width in tl_undo_preview ([bezin])
- [#4021] Fix time sensitive test ([ausi])
- [#4022] Add missing option showFilePreview to fileTree widget ([ausi])
- [#4049] Support \Attribute::TARGET_METHOD for our DI attributes ([m-vo])
- [#4060] Fix the missing request token in ModulePassword.php ([dennisbohn])
- [#4034] Fix 'Warning: Undefined array key 1' in insert tags ([xprojects-de])
- [#4032] Add a conflict for doctrine/dbal:3.3.0 ([leofeyer])
- [#4027] Also make the AvailableTransports service alias public ([fritzmg])
- [#4028] Fix replacing insert tags on non-strings ([aschempp])
- [#4030] Correctly handle parameter for requireItem ([aschempp])
- [#4001] Check `$objPage` in `Controller::getTemplate()` ([xprojects-de])
- [#4002] Add a better exception message if a page is unroutable ([leofeyer])
- [#4005] Fixed missing service name adjustments ([Toflar])
- [#3991] Fix an 'Attempt to read property "language" on null' warning ([dennisbohn])
- [#3987] Fix the available transports service ([fritzmg])
- [#4000] Make sure the `requestToken` variable is defined ([leofeyer])
- [#3979] Sort the root IDs if there is a `sorting` column ([leofeyer])
- [#3978] Change the root page icon in maintenance mode ([aschempp])
- [#3935] Allow Flysystem v3 ([m-vo])
- [#3975] Allow custom labels for the overview links ([leofeyer])
- [#3970] Handle quoted column names in the Statement class ([leofeyer])
- [#3969] Do not enable the maintenance mode for new pages ([leofeyer])
- [#3968] Correctly hash the preview file path ([ausi])
- [#3943] Generate useful error message on routing issues ([aschempp])
- [#3961] Gray out expired preview links ([leofeyer])
- [#3953] Fix the PackageUtil class ([ausi])
- [#3962] Fix the button alignment in the parent view ([leofeyer])
- [#3934] Fix the permission check for preview links ([aschempp])
- [#3949] Fix a leftover System::log call ([fritzmg])
- [#3952] Fix default log context for Email::sendTo ([SeverinGloeckle])
- [#3945] Make security.encoder_factory public again ([bytehead])

## [4.13.0-RC1] (2022-01-17)

**New features:**

- [#3613] Add a root page dependent module selector ([bytehead])
- [#3419] Add options to customize the layout inheritance for pages ([SeverinGloeckle])
- [#3774] Add a DBAFS service and integrate Flysystem ([m-vo])
- [#3872] Add front end preview links ([aschempp])
- [#3702] Add a system logger service ([SeverinGloeckle])
- [#3785] Show member groups for content elements when protected ([fritzmg])
- [#3684] Use the metadata for the player caption ([fritzmg])
- [#3180] Render be_main with custom back end controller ([m-vo])
- [#2959] Add the back end attributes and badge title to the preview toolbar ([rabauss])
- [#3498] Improve the undo module for better editor experience ([bezin])
- [#3926] Add CSS definitions for info texts in widgets ([leofeyer])
- [#3914] Show route path with regexp in page settings ([aschempp])
- [#3883] Improve the maintenance mode command ([aschempp])
- [#3848] Add file previews for downloads ([ausi])
- [#3644] Allow MODE_PARENT without child_record_callback ([fritzmg])
- [#3911] Support Typescript in the code editor ([leofeyer])
- [#3630] Support image sizes in news and calendar feeds ([bezin])
- [#3489] Add the "send newsletter" event ([SeverinGloeckle])
- [#3888] Deprecate System::getTimeZones() ([ausi])
- [#3843] Add route priority and allow the same page alias with different parameters ([aschempp])
- [#3862] Add an "overview page" field ([leofeyer])
- [#3889] Add generic toggle operation handling ([aschempp])
- [#3793] Allow creating nested folders in the file manager ([leofeyer])
- [#3737] Improve the system maintenance mode ([Toflar])
- [#3850] Add a backup retention policy ([Toflar])
- [#3729] Maintenance mode per root page ([aschempp])
- [#3628] Make image width and height overwritable in the upload widget ([doishub])
- [#3839] Remove page from index if "Do not search" is checked ([aschempp])
- [#3819] Add comments to our interfaces and abstract classes ([leofeyer])
- [#3812] Increase the length of URL fields ([fritzmg])
- [#3797] Allow previewing unroutable pages ([aschempp])
- [#3813] Replace ramsey/uuid with symfony/uid ([m-vo])
- [#3804] Always show debug log and fetch crawl status earlier ([Toflar])
- [#3798] Use unroutable pages types to limit queries ([aschempp])
- [#3605] Do not generate routes for error pages ([fritzmg])
- [#3660] Add Chosen to select menus in the backend DCA filters ([qzminski])
- [#3674] Add a DCA option to collapse inactive checkbox groups ([SeverinGloeckle])
- [#3604] Use the back end access voter instead of hasAccess() and isAllowed() ([aschempp])
- [#3615] Add the maker bundle ([sheeep])
- [#3727] Link parent elements in the back end breadcrumb trail ([Toflar])
- [#3750] Make Symfony 5.4 the minimum requirement ([leofeyer])
- [#3719] Forward error handling to routing controller ([aschempp])
- [#3614] Add a nonce to all string placeholders ([m-vo])
- [#3620] Deprecate the request_token insert tag ([m-vo])
- [#3631] Backup management on CLI ([Toflar])
- [#3611] Decorate the access decision manager ([Toflar])
- [#3706] Add a service ID linter and adjust the service IDs ([leofeyer])
- [#3686] Do not use FQCN service IDs for non-autowiring services ([leofeyer])
- [#3458] Add deprecations ([ausi])
- [#3603] Add a setting for allowed insert tags ([ausi])
- [#3619] Add PHP8 attributes for our existing service annotations ([aschempp])
- [#3659] Add a cache tag service for entity/model classes ([m-vo])
- [#3638] Add an insert tags service ([ausi])
- [#3622] Make replacing insert tags more granular ([m-vo])
- [#3472] Make the backend path configurable ([richardhj])
- [#3616] Support canonical URLs in the front end ([Toflar])
- [#3207] Relay statement parameters to doctrine dbal ([ausi])
- [#3617] Do not index documents if the canonical URL does not match ([Toflar])
- [#3625] Add a template element and module ([ausi])
- [#3609] Move the simple token parser into the String namespace ([leofeyer])
- [#3602] Add the HtmlDecoder service ([leofeyer])
- [#3606] Keep insert tags as chunked text and handle them in the HTML escaper ([m-vo])
- [#2892] Add constants for the DCA sorting modes and flags ([bezin])
- [#3535] Set the contao.web_dir parameter from composer.json ([m-vo])
- [#3230] Add blank insert tag argument to open links in new window ([ausi])
- [#3542] Support image formats AVIF, HEIC and JXL ([ausi])
- [#3523] Upgrade to Doctrine 3 ([ausi])
- [#3530] Replace patchwork/utf8 with symfony/string ([leofeyer])
- [#3391] Always show the parent trails in the tree view ([Toflar])
- [#3522] Optionally delete the home directory in the "close account" module ([leofeyer])
- [#3524] Add an event count to the event list ([leofeyer])
- [#3379] Add "Do Not Track" option to the Vimeo content element ([MarkejN])
- [#3445] Allow to pass the actual 40x page to the page type ([aschempp])
- [#3442] Change all occurrences of master (request) to main ([aschempp])
- [#3439] Use the PHP 7.4 syntax ([leofeyer])
- [#3436] Drop the contao/polyfill-symfony package ([leofeyer])
- [#3191] Use v2 of league/commonmark ([Toflar])
- [#3434] Update the dependencies and remove the BC layers ([leofeyer])

**Fixed issues:**

- [#3927] Explicitly set rootPaste, deprecate implicit rootPaste ([ausi])
- [#3937] Various small filesystem tweaks ([m-vo])
- [#3938] Remove remaining deprecations ([bytehead])
- [#3896] Improve the toggle operation ([aschempp])
- [#3909] Correctly handle types and empty values in DC_Table::save() ([aschempp])
- [#3929] Adjust the SERP preview formatting ([leofeyer])
- [#3916] Fixed tl_page permissions for routing fields ([aschempp])
- [#3912] Move the imgSize labels to the default.xlf file ([leofeyer])
- [#3917] Update maintenance response and add to preview endpoint ([aschempp])
- [#3905] Deprecate the PackageUtil class ([leofeyer])
- [#3829] Handle `$objPage` not being set in the InsertTags class ([leofeyer])
- [#3892] Fix method name to get default token value ([aschempp])
- [#3891] Fix memory issues in the backup command ([aschempp])
- [#3884] Check for unpublished elements when generating the RSS feed ([leofeyer])
- [#3885] Unify the command output format ([aschempp])
- [#3873] Stop using BE_USER_LOGGED_IN constant ([aschempp])
- [#3871] Rename the token value method ([aschempp])
- [#3866] Fix some minor issues ([leofeyer])
- [#3865] Use generic image format labels ([leofeyer])
- [#3868] Set logout response depending on scope ([bytehead])
- [#3846] Fixed debug:pages command and show dynamic content composition ([aschempp])
- [#3858] Revert replacing insert tags in the template inheritance trait ([leofeyer])
- [#3859] Deprecate two global variables ([leofeyer])
- [#3863] Harden the Picker class against undefined array keys ([leofeyer])
- [#3861] Fix the back end pagination menu ([leofeyer])
- [#3845] Register a controller for error page types ([aschempp])
- [#3816] Rework the @throws annotations ([leofeyer])
- [#3835] Remove the alias field from unroutable pages ([aschempp])
- [#3837] Do not check on null as the username can be empty ([bytehead])
- [#3810] Use mode constants in Picker widget ([bezin])
- [#3801] Add a missing isset() when checking for the mailer DSN ([aschempp])
- [#3795] Fix issues with non-admin users ([leofeyer])
- [#3799] Make the page registry service public ([aschempp])
- [#3796] Correctly handle unroutable legacy types ([aschempp])
- [#3778] Ensure type-safety when replacing legacy insert tags ([aschempp])
- [#3765] Do not deprecate the autowiring aliases ([leofeyer])
- [#3695] Switch to Symfony's version of the Path helper ([m-vo])
- [#3764] Make the autowiring aliases of renamed services public ([leofeyer])
- [#3744] Show bubbled exceptions in the pretty error screen listener ([aschempp])
- [#3743] Fix the PasswordHasherFactory usage ([bytehead])
- [#3746] Upgrade symfony/security-bundle to 5.4 and fix TokenInterface usage ([bytehead])
- [#3735] Correctly fix a wrong method usage ([leofeyer])
- [#3723] Stop using the LegacyEventDispatcherProxy class ([leofeyer])
- [#3720] Fix security permissions for custom backend paths ([aschempp])
- [#3714] Do not unnecessarily fetch the PageRoute twice ([aschempp])
- [#3705] Fix a typo in a listener ID ([leofeyer])
- [#3691] Fix an array to string conversion ([leofeyer])
- [#3696] Lower the maximum insert tag recursion level ([m-vo])
- [#3680] Fix a wrong method usage ([leofeyer])
- [#3681] Fix the fragment handler ([leofeyer])
- [#3676] Replace FragmentRendererPass with tagged locator ([aschempp])
- [#3257] Fix the Symfony 5.3 security deprecations ([bytehead])
- [#3658] Correctly check whether the root page allows canonical URLs ([leofeyer])
- [#3645] Restore backwards compatiblilty for DB Statement ([ausi])
- [#3653] Do not block the `contao.backend` namespace ([leofeyer])
- [#3643] Fix the DB query in the Versions class ([leofeyer])
- [#3641] Replace the remaining mode/flag numbers with constants ([leofeyer])
- [#3596] Fix the visible root trail check in the extended tree view ([Toflar])

[Semantic Versioning]: https://semver.org/spec/v2.0.0.html
[4.13.9]: https://github.com/contao/contao/releases/tag/4.13.9
[4.13.8]: https://github.com/contao/contao/releases/tag/4.13.8
[4.13.7]: https://github.com/contao/contao/releases/tag/4.13.7
[4.13.6]: https://github.com/contao/contao/releases/tag/4.13.6
[4.13.5]: https://github.com/contao/contao/releases/tag/4.13.5
[4.13.4]: https://github.com/contao/contao/releases/tag/4.13.4
[4.13.3]: https://github.com/contao/contao/releases/tag/4.13.3
[4.13.2]: https://github.com/contao/contao/releases/tag/4.13.2
[4.13.1]: https://github.com/contao/contao/releases/tag/4.13.1
[4.13.0]: https://github.com/contao/contao/releases/tag/4.13.0
[4.13.0-RC3]: https://github.com/contao/contao/releases/tag/4.13.0-RC3
[4.13.0-RC2]: https://github.com/contao/contao/releases/tag/4.13.0-RC2
[4.13.0-RC1]: https://github.com/contao/contao/releases/tag/4.13.0-RC1
[CVE-2022-24899]: https://github.com/contao/contao/security/advisories/GHSA-m8x6-6r63-qvj2
[AlexanderWillner]: https://github.com/AlexanderWillner
[aschempp]: https://github.com/aschempp
[ausi]: https://github.com/ausi
[bezin]: https://github.com/bezin
[bytehead]: https://github.com/bytehead
[christianbarkowsky]: https://github.com/christianbarkowsky
[CMSworker]: https://github.com/CMSworker
[de-es]: https://github.com/de-es
[dennisbohn]: https://github.com/dennisbohn
[dmolineus]: https://github.com/dmolineus
[doishub]: https://github.com/doishub
[fritzmg]: https://github.com/fritzmg
[leofeyer]: https://github.com/leofeyer
[m-vo]: https://github.com/m-vo
[MarkejN]: https://github.com/MarkejN
[MDevster]: https://github.com/MDevster
[Mynyx]: https://github.com/Mynyx
[qzminski]: https://github.com/qzminski
[rabauss]: https://github.com/rabauss
[richardhj]: https://github.com/richardhj
[SeverinGloeckle]: https://github.com/SeverinGloeckle
[sheeep]: https://github.com/sheeep
[Tastaturberuf]: https://github.com/Tastaturberuf
[Toflar]: https://github.com/Toflar
[Wusch]: https://github.com/Wusch
[xprojects-de]: https://github.com/xprojects-de
[#5277]: https://github.com/contao/contao/pull/5277
[#5269]: https://github.com/contao/contao/pull/5269
[#5268]: https://github.com/contao/contao/pull/5268
[#5260]: https://github.com/contao/contao/pull/5260
[#5249]: https://github.com/contao/contao/pull/5249
[#5240]: https://github.com/contao/contao/pull/5240
[#5234]: https://github.com/contao/contao/pull/5234
[#5227]: https://github.com/contao/contao/pull/5227
[#5222]: https://github.com/contao/contao/pull/5222
[#5221]: https://github.com/contao/contao/pull/5221
[#5206]: https://github.com/contao/contao/pull/5206
[#5194]: https://github.com/contao/contao/pull/5194
[#5185]: https://github.com/contao/contao/pull/5185
[#4571]: https://github.com/contao/contao/pull/4571
[#5099]: https://github.com/contao/contao/pull/5099
[#5167]: https://github.com/contao/contao/pull/5167
[#5170]: https://github.com/contao/contao/pull/5170
[#5165]: https://github.com/contao/contao/pull/5165
[#5137]: https://github.com/contao/contao/pull/5137
[#5159]: https://github.com/contao/contao/pull/5159
[#5112]: https://github.com/contao/contao/pull/5112
[#5142]: https://github.com/contao/contao/pull/5142
[#5104]: https://github.com/contao/contao/pull/5104
[#5092]: https://github.com/contao/contao/pull/5092
[#4979]: https://github.com/contao/contao/pull/4979
[#5064]: https://github.com/contao/contao/pull/5064
[#4988]: https://github.com/contao/contao/pull/4988
[#5009]: https://github.com/contao/contao/pull/5009
[#5061]: https://github.com/contao/contao/pull/5061
[#5129]: https://github.com/contao/contao/pull/5129
[#5108]: https://github.com/contao/contao/pull/5108
[#5016]: https://github.com/contao/contao/pull/5016
[#5095]: https://github.com/contao/contao/pull/5095
[#5098]: https://github.com/contao/contao/pull/5098
[#5071]: https://github.com/contao/contao/pull/5071
[#5059]: https://github.com/contao/contao/pull/5059
[#5026]: https://github.com/contao/contao/pull/5026
[#5020]: https://github.com/contao/contao/pull/5020
[#4965]: https://github.com/contao/contao/pull/4965
[#4973]: https://github.com/contao/contao/pull/4973
[#4957]: https://github.com/contao/contao/pull/4957
[#4961]: https://github.com/contao/contao/pull/4961
[#4952]: https://github.com/contao/contao/pull/4952
[#4933]: https://github.com/contao/contao/pull/4933
[#4948]: https://github.com/contao/contao/pull/4948
[#4941]: https://github.com/contao/contao/pull/4941
[#4932]: https://github.com/contao/contao/pull/4932
[#4808]: https://github.com/contao/contao/pull/4808
[#4945]: https://github.com/contao/contao/pull/4945
[#4926]: https://github.com/contao/contao/pull/4926
[#4870]: https://github.com/contao/contao/pull/4870
[#4878]: https://github.com/contao/contao/pull/4878
[#4891]: https://github.com/contao/contao/pull/4891
[#4889]: https://github.com/contao/contao/pull/4889
[#4881]: https://github.com/contao/contao/pull/4881
[#4883]: https://github.com/contao/contao/pull/4883
[#4886]: https://github.com/contao/contao/pull/4886
[#4877]: https://github.com/contao/contao/pull/4877
[#4848]: https://github.com/contao/contao/pull/4848
[#4850]: https://github.com/contao/contao/pull/4850
[#4861]: https://github.com/contao/contao/pull/4861
[#4857]: https://github.com/contao/contao/pull/4857
[#4849]: https://github.com/contao/contao/pull/4849
[#4833]: https://github.com/contao/contao/pull/4833
[#4778]: https://github.com/contao/contao/pull/4778
[#4838]: https://github.com/contao/contao/pull/4838
[#4836]: https://github.com/contao/contao/pull/4836
[#4807]: https://github.com/contao/contao/pull/4807
[#4794]: https://github.com/contao/contao/pull/4794
[#4786]: https://github.com/contao/contao/pull/4786
[#4782]: https://github.com/contao/contao/pull/4782
[#4799]: https://github.com/contao/contao/pull/4799
[#3924]: https://github.com/contao/contao/pull/3924
[#4665]: https://github.com/contao/contao/pull/4665
[#4672]: https://github.com/contao/contao/pull/4672
[#4785]: https://github.com/contao/contao/pull/4785
[#4784]: https://github.com/contao/contao/pull/4784
[#4757]: https://github.com/contao/contao/pull/4757
[#4763]: https://github.com/contao/contao/pull/4763
[#4774]: https://github.com/contao/contao/pull/4774
[#4767]: https://github.com/contao/contao/pull/4767
[#4679]: https://github.com/contao/contao/pull/4679
[#4752]: https://github.com/contao/contao/pull/4752
[#4732]: https://github.com/contao/contao/pull/4732
[#4747]: https://github.com/contao/contao/pull/4747
[#4728]: https://github.com/contao/contao/pull/4728
[#4733]: https://github.com/contao/contao/pull/4733
[#4739]: https://github.com/contao/contao/pull/4739
[#4720]: https://github.com/contao/contao/pull/4720
[#4719]: https://github.com/contao/contao/pull/4719
[#4713]: https://github.com/contao/contao/pull/4713
[#4717]: https://github.com/contao/contao/pull/4717
[#4692]: https://github.com/contao/contao/pull/4692
[#4691]: https://github.com/contao/contao/pull/4691
[#4683]: https://github.com/contao/contao/pull/4683
[#4673]: https://github.com/contao/contao/pull/4673
[#4662]: https://github.com/contao/contao/pull/4662
[#4443]: https://github.com/contao/contao/pull/4443
[#4681]: https://github.com/contao/contao/pull/4681
[#4669]: https://github.com/contao/contao/pull/4669
[#4668]: https://github.com/contao/contao/pull/4668
[#4667]: https://github.com/contao/contao/pull/4667
[#4656]: https://github.com/contao/contao/pull/4656
[#4643]: https://github.com/contao/contao/pull/4643
[#4632]: https://github.com/contao/contao/pull/4632
[#4641]: https://github.com/contao/contao/pull/4641
[#4623]: https://github.com/contao/contao/pull/4623
[#4617]: https://github.com/contao/contao/pull/4617
[#4592]: https://github.com/contao/contao/pull/4592
[#4631]: https://github.com/contao/contao/pull/4631
[#4627]: https://github.com/contao/contao/pull/4627
[#4608]: https://github.com/contao/contao/pull/4608
[#4329]: https://github.com/contao/contao/pull/4329
[#4506]: https://github.com/contao/contao/pull/4506
[#4504]: https://github.com/contao/contao/pull/4504
[#4514]: https://github.com/contao/contao/pull/4514
[#4603]: https://github.com/contao/contao/pull/4603
[#4396]: https://github.com/contao/contao/pull/4396
[#4604]: https://github.com/contao/contao/pull/4604
[#4601]: https://github.com/contao/contao/pull/4601
[#4600]: https://github.com/contao/contao/pull/4600
[#4599]: https://github.com/contao/contao/pull/4599
[#3995]: https://github.com/contao/contao/pull/3995
[#4501]: https://github.com/contao/contao/pull/4501
[#4489]: https://github.com/contao/contao/pull/4489
[#4573]: https://github.com/contao/contao/pull/4573
[#4567]: https://github.com/contao/contao/pull/4567
[#4523]: https://github.com/contao/contao/pull/4523
[#4549]: https://github.com/contao/contao/pull/4549
[#4534]: https://github.com/contao/contao/pull/4534
[#4535]: https://github.com/contao/contao/pull/4535
[#4533]: https://github.com/contao/contao/pull/4533
[#4527]: https://github.com/contao/contao/pull/4527
[#4521]: https://github.com/contao/contao/pull/4521
[#4456]: https://github.com/contao/contao/pull/4456
[#4437]: https://github.com/contao/contao/pull/4437
[#4451]: https://github.com/contao/contao/pull/4451
[#4448]: https://github.com/contao/contao/pull/4448
[#4447]: https://github.com/contao/contao/pull/4447
[#4431]: https://github.com/contao/contao/pull/4431
[#4433]: https://github.com/contao/contao/pull/4433
[#4426]: https://github.com/contao/contao/pull/4426
[#4303]: https://github.com/contao/contao/pull/4303
[#4301]: https://github.com/contao/contao/pull/4301
[#4425]: https://github.com/contao/contao/pull/4425
[#4410]: https://github.com/contao/contao/pull/4410
[#4179]: https://github.com/contao/contao/pull/4179
[#4297]: https://github.com/contao/contao/pull/4297
[#4346]: https://github.com/contao/contao/pull/4346
[#4397]: https://github.com/contao/contao/pull/4397
[#4398]: https://github.com/contao/contao/pull/4398
[#4311]: https://github.com/contao/contao/pull/4311
[#4353]: https://github.com/contao/contao/pull/4353
[#4302]: https://github.com/contao/contao/pull/4302
[#4376]: https://github.com/contao/contao/pull/4376
[#4374]: https://github.com/contao/contao/pull/4374
[#4359]: https://github.com/contao/contao/pull/4359
[#4300]: https://github.com/contao/contao/pull/4300
[#4287]: https://github.com/contao/contao/pull/4287
[#4288]: https://github.com/contao/contao/pull/4288
[#4292]: https://github.com/contao/contao/pull/4292
[#4026]: https://github.com/contao/contao/pull/4026
[#4281]: https://github.com/contao/contao/pull/4281
[#4279]: https://github.com/contao/contao/pull/4279
[#4272]: https://github.com/contao/contao/pull/4272
[#4273]: https://github.com/contao/contao/pull/4273
[#4270]: https://github.com/contao/contao/pull/4270
[#4275]: https://github.com/contao/contao/pull/4275
[#4274]: https://github.com/contao/contao/pull/4274
[#4271]: https://github.com/contao/contao/pull/4271
[#4197]: https://github.com/contao/contao/pull/4197
[#4269]: https://github.com/contao/contao/pull/4269
[#4268]: https://github.com/contao/contao/pull/4268
[#4267]: https://github.com/contao/contao/pull/4267
[#4158]: https://github.com/contao/contao/pull/4158
[#4228]: https://github.com/contao/contao/pull/4228
[#4261]: https://github.com/contao/contao/pull/4261
[#4262]: https://github.com/contao/contao/pull/4262
[#4264]: https://github.com/contao/contao/pull/4264
[#4254]: https://github.com/contao/contao/pull/4254
[#4202]: https://github.com/contao/contao/pull/4202
[#4265]: https://github.com/contao/contao/pull/4265
[#4259]: https://github.com/contao/contao/pull/4259
[#4245]: https://github.com/contao/contao/pull/4245
[#4244]: https://github.com/contao/contao/pull/4244
[#4216]: https://github.com/contao/contao/pull/4216
[#4230]: https://github.com/contao/contao/pull/4230
[#4224]: https://github.com/contao/contao/pull/4224
[#4221]: https://github.com/contao/contao/pull/4221
[#4208]: https://github.com/contao/contao/pull/4208
[#4183]: https://github.com/contao/contao/pull/4183
[#4162]: https://github.com/contao/contao/pull/4162
[#4123]: https://github.com/contao/contao/pull/4123
[#4151]: https://github.com/contao/contao/pull/4151
[#4149]: https://github.com/contao/contao/pull/4149
[#4133]: https://github.com/contao/contao/pull/4133
[#4141]: https://github.com/contao/contao/pull/4141
[#4145]: https://github.com/contao/contao/pull/4145
[#4126]: https://github.com/contao/contao/pull/4126
[#4143]: https://github.com/contao/contao/pull/4143
[#4139]: https://github.com/contao/contao/pull/4139
[#4121]: https://github.com/contao/contao/pull/4121
[#4140]: https://github.com/contao/contao/pull/4140
[#4136]: https://github.com/contao/contao/pull/4136
[#4117]: https://github.com/contao/contao/pull/4117
[#4134]: https://github.com/contao/contao/pull/4134
[#3990]: https://github.com/contao/contao/pull/3990
[#4004]: https://github.com/contao/contao/pull/4004
[#4042]: https://github.com/contao/contao/pull/4042
[#4099]: https://github.com/contao/contao/pull/4099
[#4114]: https://github.com/contao/contao/pull/4114
[#4113]: https://github.com/contao/contao/pull/4113
[#4112]: https://github.com/contao/contao/pull/4112
[#4103]: https://github.com/contao/contao/pull/4103
[#3992]: https://github.com/contao/contao/pull/3992
[#4096]: https://github.com/contao/contao/pull/4096
[#4065]: https://github.com/contao/contao/pull/4065
[#4078]: https://github.com/contao/contao/pull/4078
[#4095]: https://github.com/contao/contao/pull/4095
[#4094]: https://github.com/contao/contao/pull/4094
[#4093]: https://github.com/contao/contao/pull/4093
[#4074]: https://github.com/contao/contao/pull/4074
[#4012]: https://github.com/contao/contao/pull/4012
[#4052]: https://github.com/contao/contao/pull/4052
[#4046]: https://github.com/contao/contao/pull/4046
[#3994]: https://github.com/contao/contao/pull/3994
[#4057]: https://github.com/contao/contao/pull/4057
[#4021]: https://github.com/contao/contao/pull/4021
[#4022]: https://github.com/contao/contao/pull/4022
[#4049]: https://github.com/contao/contao/pull/4049
[#4060]: https://github.com/contao/contao/pull/4060
[#4034]: https://github.com/contao/contao/pull/4034
[#4032]: https://github.com/contao/contao/pull/4032
[#4027]: https://github.com/contao/contao/pull/4027
[#4028]: https://github.com/contao/contao/pull/4028
[#4030]: https://github.com/contao/contao/pull/4030
[#4001]: https://github.com/contao/contao/pull/4001
[#4002]: https://github.com/contao/contao/pull/4002
[#4005]: https://github.com/contao/contao/pull/4005
[#3991]: https://github.com/contao/contao/pull/3991
[#3987]: https://github.com/contao/contao/pull/3987
[#4000]: https://github.com/contao/contao/pull/4000
[#3979]: https://github.com/contao/contao/pull/3979
[#3978]: https://github.com/contao/contao/pull/3978
[#3935]: https://github.com/contao/contao/pull/3935
[#3975]: https://github.com/contao/contao/pull/3975
[#3970]: https://github.com/contao/contao/pull/3970
[#3969]: https://github.com/contao/contao/pull/3969
[#3968]: https://github.com/contao/contao/pull/3968
[#3943]: https://github.com/contao/contao/pull/3943
[#3961]: https://github.com/contao/contao/pull/3961
[#3953]: https://github.com/contao/contao/pull/3953
[#3962]: https://github.com/contao/contao/pull/3962
[#3934]: https://github.com/contao/contao/pull/3934
[#3949]: https://github.com/contao/contao/pull/3949
[#3952]: https://github.com/contao/contao/pull/3952
[#3945]: https://github.com/contao/contao/pull/3945
[#3613]: https://github.com/contao/contao/pull/3613
[#3419]: https://github.com/contao/contao/pull/3419
[#3774]: https://github.com/contao/contao/pull/3774
[#3872]: https://github.com/contao/contao/pull/3872
[#3702]: https://github.com/contao/contao/pull/3702
[#3785]: https://github.com/contao/contao/pull/3785
[#3684]: https://github.com/contao/contao/pull/3684
[#3180]: https://github.com/contao/contao/pull/3180
[#2959]: https://github.com/contao/contao/pull/2959
[#3498]: https://github.com/contao/contao/pull/3498
[#3926]: https://github.com/contao/contao/pull/3926
[#3914]: https://github.com/contao/contao/pull/3914
[#3883]: https://github.com/contao/contao/pull/3883
[#3848]: https://github.com/contao/contao/pull/3848
[#3644]: https://github.com/contao/contao/pull/3644
[#3911]: https://github.com/contao/contao/pull/3911
[#3630]: https://github.com/contao/contao/pull/3630
[#3489]: https://github.com/contao/contao/pull/3489
[#3888]: https://github.com/contao/contao/pull/3888
[#3843]: https://github.com/contao/contao/pull/3843
[#3862]: https://github.com/contao/contao/pull/3862
[#3889]: https://github.com/contao/contao/pull/3889
[#3793]: https://github.com/contao/contao/pull/3793
[#3737]: https://github.com/contao/contao/pull/3737
[#3850]: https://github.com/contao/contao/pull/3850
[#3729]: https://github.com/contao/contao/pull/3729
[#3628]: https://github.com/contao/contao/pull/3628
[#3839]: https://github.com/contao/contao/pull/3839
[#3819]: https://github.com/contao/contao/pull/3819
[#3812]: https://github.com/contao/contao/pull/3812
[#3797]: https://github.com/contao/contao/pull/3797
[#3813]: https://github.com/contao/contao/pull/3813
[#3804]: https://github.com/contao/contao/pull/3804
[#3798]: https://github.com/contao/contao/pull/3798
[#3605]: https://github.com/contao/contao/pull/3605
[#3660]: https://github.com/contao/contao/pull/3660
[#3674]: https://github.com/contao/contao/pull/3674
[#3604]: https://github.com/contao/contao/pull/3604
[#3615]: https://github.com/contao/contao/pull/3615
[#3727]: https://github.com/contao/contao/pull/3727
[#3750]: https://github.com/contao/contao/pull/3750
[#3719]: https://github.com/contao/contao/pull/3719
[#3614]: https://github.com/contao/contao/pull/3614
[#3620]: https://github.com/contao/contao/pull/3620
[#3631]: https://github.com/contao/contao/pull/3631
[#3611]: https://github.com/contao/contao/pull/3611
[#3706]: https://github.com/contao/contao/pull/3706
[#3686]: https://github.com/contao/contao/pull/3686
[#3458]: https://github.com/contao/contao/pull/3458
[#3603]: https://github.com/contao/contao/pull/3603
[#3619]: https://github.com/contao/contao/pull/3619
[#3659]: https://github.com/contao/contao/pull/3659
[#3638]: https://github.com/contao/contao/pull/3638
[#3622]: https://github.com/contao/contao/pull/3622
[#3472]: https://github.com/contao/contao/pull/3472
[#3616]: https://github.com/contao/contao/pull/3616
[#3207]: https://github.com/contao/contao/pull/3207
[#3617]: https://github.com/contao/contao/pull/3617
[#3625]: https://github.com/contao/contao/pull/3625
[#3609]: https://github.com/contao/contao/pull/3609
[#3602]: https://github.com/contao/contao/pull/3602
[#3606]: https://github.com/contao/contao/pull/3606
[#2892]: https://github.com/contao/contao/pull/2892
[#3535]: https://github.com/contao/contao/pull/3535
[#3230]: https://github.com/contao/contao/pull/3230
[#3542]: https://github.com/contao/contao/pull/3542
[#3523]: https://github.com/contao/contao/pull/3523
[#3530]: https://github.com/contao/contao/pull/3530
[#3391]: https://github.com/contao/contao/pull/3391
[#3522]: https://github.com/contao/contao/pull/3522
[#3524]: https://github.com/contao/contao/pull/3524
[#3379]: https://github.com/contao/contao/pull/3379
[#3445]: https://github.com/contao/contao/pull/3445
[#3442]: https://github.com/contao/contao/pull/3442
[#3439]: https://github.com/contao/contao/pull/3439
[#3436]: https://github.com/contao/contao/pull/3436
[#3191]: https://github.com/contao/contao/pull/3191
[#3434]: https://github.com/contao/contao/pull/3434
[#3927]: https://github.com/contao/contao/pull/3927
[#3937]: https://github.com/contao/contao/pull/3937
[#3938]: https://github.com/contao/contao/pull/3938
[#3896]: https://github.com/contao/contao/pull/3896
[#3909]: https://github.com/contao/contao/pull/3909
[#3929]: https://github.com/contao/contao/pull/3929
[#3916]: https://github.com/contao/contao/pull/3916
[#3912]: https://github.com/contao/contao/pull/3912
[#3917]: https://github.com/contao/contao/pull/3917
[#3905]: https://github.com/contao/contao/pull/3905
[#3829]: https://github.com/contao/contao/pull/3829
[#3892]: https://github.com/contao/contao/pull/3892
[#3891]: https://github.com/contao/contao/pull/3891
[#3884]: https://github.com/contao/contao/pull/3884
[#3885]: https://github.com/contao/contao/pull/3885
[#3873]: https://github.com/contao/contao/pull/3873
[#3871]: https://github.com/contao/contao/pull/3871
[#3866]: https://github.com/contao/contao/pull/3866
[#3865]: https://github.com/contao/contao/pull/3865
[#3868]: https://github.com/contao/contao/pull/3868
[#3846]: https://github.com/contao/contao/pull/3846
[#3858]: https://github.com/contao/contao/pull/3858
[#3859]: https://github.com/contao/contao/pull/3859
[#3863]: https://github.com/contao/contao/pull/3863
[#3861]: https://github.com/contao/contao/pull/3861
[#3845]: https://github.com/contao/contao/pull/3845
[#3816]: https://github.com/contao/contao/pull/3816
[#3835]: https://github.com/contao/contao/pull/3835
[#3837]: https://github.com/contao/contao/pull/3837
[#3810]: https://github.com/contao/contao/pull/3810
[#3801]: https://github.com/contao/contao/pull/3801
[#3795]: https://github.com/contao/contao/pull/3795
[#3799]: https://github.com/contao/contao/pull/3799
[#3796]: https://github.com/contao/contao/pull/3796
[#3778]: https://github.com/contao/contao/pull/3778
[#3765]: https://github.com/contao/contao/pull/3765
[#3695]: https://github.com/contao/contao/pull/3695
[#3764]: https://github.com/contao/contao/pull/3764
[#3744]: https://github.com/contao/contao/pull/3744
[#3743]: https://github.com/contao/contao/pull/3743
[#3746]: https://github.com/contao/contao/pull/3746
[#3735]: https://github.com/contao/contao/pull/3735
[#3723]: https://github.com/contao/contao/pull/3723
[#3720]: https://github.com/contao/contao/pull/3720
[#3714]: https://github.com/contao/contao/pull/3714
[#3705]: https://github.com/contao/contao/pull/3705
[#3691]: https://github.com/contao/contao/pull/3691
[#3696]: https://github.com/contao/contao/pull/3696
[#3680]: https://github.com/contao/contao/pull/3680
[#3681]: https://github.com/contao/contao/pull/3681
[#3676]: https://github.com/contao/contao/pull/3676
[#3257]: https://github.com/contao/contao/pull/3257
[#3658]: https://github.com/contao/contao/pull/3658
[#3645]: https://github.com/contao/contao/pull/3645
[#3653]: https://github.com/contao/contao/pull/3653
[#3643]: https://github.com/contao/contao/pull/3643
[#3641]: https://github.com/contao/contao/pull/3641
[#3596]: https://github.com/contao/contao/pull/3596
