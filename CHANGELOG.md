# Changelog

This project adheres to [Semantic Versioning].

## [4.9.33] (2022-08-17)

**Fixed issues:**

- [#5013] Do not insert fields that do not exist in the DB ([aschempp])
- [#5164] Fix check for existing label in DcaLoader ([ausi])
- [#5130] Only set login constants in master request ([fritzmg])
- [#5155] Deprecate Calendar::addEvent() ([ausi])
- [#5143] Do not run the DC constructor inside the picker widget ([ausi])
- [#5132] Check for column options in AdjustSearchUrlLengthListener ([fritzmg])
- [#5154] Allow empty key for subpalette suffix based on select ([dennisbohn])
- [#5145] Fix typo in tl_comments ([ausi])

## [4.9.32] (2022-08-12)

**Fixed issues:**

- [#5123] Remove the "draft" overlay ([leofeyer])
- [#5121] Remove leftover code in the DataContainer class ([fritzmg])
- [#4628] Automatically reduce tl_search.url length ([fritzmg])
- [#4887] Update the Google Analytics tracking snippet ([ameotoko])
- [#5072] Backport lazy services for CronJob repository ([aschempp])
- [#5101] Check database version in migrate command ([ausi])
- [#4777] Fix the missing cache tag invalidation in tree view ([Toflar])
- [#5075] Do not include "index" in folderUrl ([aschempp])
- [#4968] Set login constants in request listener ([fritzmg])
- [#5032] Allow null for PaletteManipulator parent ([fritzmg])
- [#5077] Fix indeterministic order for non-unique filters in DC_Table ([Toflar])
- [#5010] Fix adjustTime in tl_calendar_events ([fritzmg])
- [#5102] Make sure the framework is initialized when accessing config ([aschempp])
- [#5058] Fix illegal string offsets in the translator ([ausi])
- [#4964] Fix PHP 8 warning in ModuleRegistration ([ameotoko])
- [#4975] Fix tl_member source translations for city and state ([zoglo])
- [#4966] Use cache_suffix for TinyMCE ([fritzmg])
- [#4366] Revise drafts only when navigating back ([ausi])
- [#4895] Fix an error in page forward if the $_GET parameter is an array ([qzminski])

## [4.9.31] (2022-07-05)

**Fixed issues:**

- [#4865] Correctly check ptable when moving records ([aschempp])
- [#4864] Fix inconsistency when copying child records ([aschempp])
- [#4906] Improve deprecation warning for DCA FQCN ([aschempp])
- [#4894] Display an error message if the version cannot be restored ([qzminski])
- [#4880] Adjust the video file type sorting in the media element ([leofeyer])
- [#4867] Do not update the search index on redirects ([Toflar])
- [#4879] Do not call `kernel.terminate` on fresh cache entries ([Toflar])
- [#4858] Fix 0 to nbsp conversion in the listing module ([bezin])
- [#4853] Determine the InnoDB index length only once per schema ([ausi])
- [#4845] Fix the crawl logs view ([leofeyer])
- [#4828] Correctly clear style sheets on 404 ([aschempp])
- [#4832] Prevent unlimited recurrences in the event list ([leofeyer])
- [#4574] Fix the impersonation message ([cliffparnitzky])
- [#4819] Deprecate CURRENT_ID ([ausi])
- [#4809] Add a migration for bad playerColor data ([ausi])
- [#4814] Fix preview toolbar always showing error ([fritzmg])
- [#4804] Fix SearchIndexSubscriber not respecting noindex hint ([Toflar])
- [#4802] Ignore website roots with empty language in the meta wizard ([fritzmg])

## [4.9.30] (2022-06-03)

**Fixed issues:**

- [#4766] Exclude the ModuleWizard checkbox from keyboard access ([aschempp])
- [#4711] Correctly support legacy DC names in picker provider ([aschempp])
- [#4718] Fix DBAFS when upload_path contains subfolders ([fritzmg])
- [#4708] Increase the tl_comments_notify.url length ([fritzmg])
- [#4727] Deprecate REQUEST_TOKEN ([ausi])
- [#3742] Show error if preview settings could not be applied ([fritzmg])
- [#4690] Handle cases when layoutId is not set on page model ([bezin])
- [#4698] Fix broken filemanager for unsupported images ([ausi])
- [#4686] Always delete indexed page for unsuccessful responses ([fritzmg])
- [#4693] Reduce the row sizes of tl_module and tl_content ([ausi])
- [#4680] Fix the non-JS navigation toggle on backend dashboard ([aschempp])
- [#4670] Fix type warning in event list ([fritzmg])
- [#4633] Undeprecate contao.csrf.token_manager ([fritzmg])
- [#4647] Improve the news archive permission labels ([Toflar])
- [#4638] Deprecate Frontend::getCronTimeout ([fritzmg])
- [#4622] Always set a mock session when creating feeds ([fritzmg])
- [#4621] Correctly handle rgxp natural text field attributes ([aschempp])

## [4.9.29] (2022-05-05)

**Fixed issues:**

- [#4519] Fix ESI fragments with inline attributes ([aschempp])
- [#3711] Fix being unable to re-register after token expired ([fritzmg])
- [#4605] Ignore port in Favicon and RobotsTxt controller ([bezin])
- [#3887] Make sure the parent directory exists when writing files ([aschempp])
- [#4596] Fix upload directory check in ImageFactory on Windows ([fritzmg])
- [#4595] Support more widgets in DC_Folder ([fritzmg])
- [#4590] Require symfony/deprecation-contracts ([leofeyer])
- [#4586] Also ignore the Resources folder ([aschempp])
- [#4588] Backport loop as an option to YouTube videos to 4.9 ([fritzmg])
- [#4497] Revert the DcaExtractor changes ([fritzmg])
- [#4500] Fix header fields for blank option ([rabauss])
- [#4477] Fix undefined value/unit for empty array in headline ([rabauss])
- [#4463] Sort the selectable templates in install tool ([cliffparnitzky])
- [#4570] Allow 0 in option wizard as valid input ([bezin])
- [#4481] Fix AbstractTablePickerProvider ([bytehead])
- [#4528] Only save personal data if there is no error ([aschempp])
- [#4195] Set a session for the fake request ([fritzmg])
- [#4470] Update version number in toggleSubpalette ([ausi])
- [#4493] Use fixed subject string for List-Unsubscribe header ([aschempp])
- [#4509] Fix bug with hash comparison in migrate command ([ausi])
- [#4487] Remove rel="nofollow" for internal pages ([fritzmg])
- [#4192] Shorten the version description to the length of the DB column ([cliffparnitzky])
- [#4320] Fix missing hash option for migrate command ([ausi])
- [#4455] Fix Atom feed generation ([fritzmg])

## [4.9.28] (2022-03-31)

**Fixed issues:**

- [#4427] Handle long page titles and URLs in the search table ([leofeyer])
- [#4424] Also hover over the parent records in "paste" mode ([leofeyer])
- [#4423] Decode the folder name when checking for circular references ([leofeyer])
- [#4219] Make the contao:install:lock command idempotent ([richardhj])
- [#4408] Improve insert tags speed ([ausi])
- [#4252] Fix the trigger_error() calls ([leofeyer])
- [#4402] Fixed nested http client options not supported ([Toflar])
- [#4357] Fix srcToInsertTag() for URL encoded paths ([fritzmg])
- [#4294] Rename the Controller::reset() method ([aschempp])
- [#4331] Fix typos in the Environment class ([fritzmg])
- [#4293] Revert 'Do not use the `.tl_confirm` CSS class in the front end' ([leofeyer])
- [#4299] Fix invalid news content element ID ([ausi])
- [#4263] Make the search accent insensitive ([ausi])
- [#4256] Allow fragment reference with fake model instance ([aschempp])
- [#4236] Also create a new version if a checkbox is auto-submitted ([leofeyer])
- [#4373] Also clean textarea of caption for last metawizard ([aschempp])
- [#4322] Use FQCN instead of aliased class name ([bytehead])
- [#4266] Forward IO to Composer filesystem in ScriptHandler ([aschempp])
- [#4285] Fix DCA extractor wrongly assuming every DCA is database driven ([fritzmg])

## [4.9.27] (2022-03-09)

**Fixed issues:**

- [#4248] Correctly encode redirect URLs between 401 pages and login forms ([leonexcc])
- [#4241] Do not use the `.tl_confirm` CSS class in the front end ([leofeyer])
- [#4235] Add the `|urlattr` flag in the `StringUtil::srcToInsertTag()` method ([leofeyer])
- [#4237] Handle `0` as widget attribute value ([leofeyer])
- [#4233] Make `config.sql` optional in the DCA ([leofeyer])
- [#4234] Create new versions after the onsubmit_callback in the registration module ([leofeyer])
- [#4232] Limit the number of suggestions in the front end preview ([leofeyer])
- [#4231] Remove the `$Template` property from the PageRegular class again ([leofeyer])
- [#4229] Use the Contao short URL service for keyboard shortcuts ([leofeyer])
- [#4150] Improve sorting in the system log ([bennyborn])
- [#4091] Lazy call router when replacing old backend paths ([aschempp])
- [#4199] Deprecate the ContaoFrameworkInterface service ([aschempp])
- [#4212] Unset key in URL when switching edit mode ([aschempp])
- [#4135] Do not configure the default clickjacking paths in the skeleton ([fritzmg])
- [#4218] Do not process redirected URLs outside base domains ([fritzmg])
- [#4225] Remove the wizard if a DCA field is not editable ([aschempp])
- [#4227] Support "submitOnChange" in the PageTree and Picker widgets ([aschempp])
- [#4226] Deprecate the Page*::generate() methods ([aschempp])
- [#4210] Fix warning in ModuleArticle if page object is null ([bezin])
- [#4204] Allow underscores in tag attributes ([bytehead])
- [#4155] Allow `<summary>` and `<details>` tags by default ([Toflar])
- [#4083] Unlock flatten option for imagine configuration ([rabauss])
- [#4105] Fix trailing slashes in URL for Contao 4.9 ([aschempp])

## [4.9.26] (2022-02-08)

**Fixed issues:**

- [#4045] Correctly strip all CSRF token occurrences ([ausi])
- [#4075] Remove the `Controller::$Template` property ([leofeyer])
- [#4072] Reduce memory consumption when warming up cache ([Toflar])
- [#4067] Correctly handle null values in DCs ([ausi])

## [4.9.25] (2022-02-03)

**Fixed issues:**

- [#3997] Fix several array to string conversions ([leofeyer])
- [#4056] Fix merging cssID from ContentModule to FrontendModuleController ([ameotoko])
- [#3857] Fix fragments in RSS feeds ([fritzmg])
- [#3800] Fix routing with local domain and port ([bezin])
- [#4009] Fix long content being cut off in the back end popup ([qzminski])
- [#4031] Backport 'Replace phpunit/token-stream with nikic/php-parser' ([m-vo])
- [#4020] Fix compatibility with Composer 2.2.5 ([ausi])
- [#4013] Reload translations if the language changes ([ausi])
- [#3984] Add the config.allow-plugins settings to the composer.json files ([leofeyer])
- [#3971] Fix `Input::post()` for form data ([fritzmg])
- [#3981] Reduce the scope of the authentication listener ([fritzmg])
- [#3790] Add a debounce wrapper to the username autosuggester ([ameotoko])
- [#3488] Add a configuration for allowed URL protocols ([MarkejN])
- [#3910] Fix the ScriptHandlers for Composer 2.3 ([fritzmg])
- [#3915] Do not generate URLs for insert tags that don’t need it ([aschempp])
- [#3842] Simplify expression in Crawl\Escargot\Factory ([m-vo])
- [#3966] Add compatibility with symfony/filesystem 5.4 ([ausi])
- [#3939] Fix duplicating child records with dynamic ptable ([dmolineus])
- [#3876] Correctly handle array values in DCs ([aschempp])
- [#3882] Do not initialize the framework to get the Automator commands ([aschempp])
- [#3877] Enforce final newline in HTML5 templates ([aschempp])
- [#3836] Report HTTP exceptions as warnings for the crawler instead of errors ([Toflar])
- [#3867] Serialize array value before saving ([bytehead])
- [#3841] Remove an unused setup method ([aschempp])

## [4.9.24] (2021-12-16)

**Fixed issues:**

- [#3822] Add a Template property to the Controller class ([leofeyer])
- [#3815] Enable image lazy loading in file manager ([fritzmg])
- [#3803] Fix infinite 4.8.0 migration when custom templates are present ([fritzmg])
- [#3792] Fix more PHP8 warnings ([aschempp])
- [#3788] Clarify internal style sheets deprecation ([fritzmg])
- [#3787] Do not sort the referrers ([leofeyer])
- [#3781] Fix unknown "ansi_to_html" filter error ([fritzmg])
- [#3759] Unify the MakeServicesPublicPass ([bytehead])
- [#3758] Add transitive dependency on symfony/twig-bridge ([richardhj])

## [4.9.23] (2021-12-02)

**Fixed issues:**

- [#3741] Make sure GUIDs in RSS feeds are unique ([leofeyer])
- [#3732] Disable form autocomplete for preview bar ([fritzmg])
- [#3738] Fix the back end tree view CSS ([leofeyer])
- [#3734] Delete uploaded files if a form does not validate ([fritzmg])
- [#3470] Make sure .public is a file and not a directory ([fritzmg])
- [#3692] Remove the script name before signing a redirect URI ([bytehead])
- [#3690] Correctly store the referrer in the back end ([Toflar])
- [#3699] Use ClockMock for time sensitive remember me token test ([SeverinGloeckle])
- [#3647] Set language of redirect page in global context ([rabauss])
- [#3668] Do not automatically focus input fields with autocomplete ([SeverinGloeckle])
- [#3601] Inline fragments should not compile the front end template ([aschempp])
- [#3656] Do not set the global page object in the back end ([leofeyer])
- [#3654] Ensure defined admin email is always used as sender ([fritzmg])
- [#3665] Handle doctrine binary types in version compare view ([SeverinGloeckle])
- [#3637] Fix the decorator pattern in the FailTolerantProxyCacheWarmer ([aschempp])
- [#3623] Do not set the server_version ([ausi])
- [#3627] Add the missing dot-env features ([aschempp])
- [#3607] Ignore routes without root ID instead of throwing an exception ([aschempp])
- [#3608] Only change the cache header if no response is passed to Twig render() ([aschempp])
- [#3595] Fix the base path in the asset context ([aschempp])
- [#3598] Adjust cache headers for Twig responses ([aschempp])
- [#3599] Move interest-cohort header to response listener ([aschempp])

## [4.9.22] (2021-10-20)

**Fixed issues:**

- [#3585] Fix link_url::back insert tag backwards compatibility ([ausi])
- [#3527] Update the Facebook share link ([aschempp])
- [#3134] Use the driver from the database URL if there is one ([aschempp])
- [#3571] Correctly check for tinyMCE ([aschempp])
- [#3548] Fix pagination item calculation for an even number of links ([SeverinGloeckle])

## [4.9.21] (2021-10-05)

**New features:**

- [#3352] Also pass the template object in the "parseFrontendTemplate" hook ([xprojects-de])

**Fixed issues:**

- [#3543] Fix the meta wizard language menu ([leofeyer])
- [#3496] Handle edge cases in StringUtil::convertEncoding() ([SeverinGloeckle])
- [#3534] Translate all languages in the file meta data ([aschempp])
- [#3526] Improve the JwtManager ([aschempp])
- [#3503] Correctly handle empty image size formats ([aschempp])
- [#3521] Document another known limitation ([leofeyer])
- [#3519] Do not encode option values ([ausi])
- [#3517] Merge duplicate tags in HTML attributes config ([ausi])
- [#3513] Allow HTML by default only for tinyMCE and ace|html ([ausi])
- [#3512] Decode the subject when sending form submission via email ([ausi])
- [#3508] Unset the noComments field if the comments bundle is not installed ([leofeyer])
- [#3499] Load DataContainer before determining empty value ([fritzmg])
- [#3497] Fix the option sorting in the back end drop-downs ([leofeyer])
- [#3475] Allow WebP to PNG/JPG conversion ([fritzmg])
- [#3480] Correctly handle empty values in the registration module ([fritzmg])
- [#3481] Fix inconsistencies in the search field rendering ([leofeyer])
- [#3464] Redirect if the back end is called via the front end preview entry point ([leofeyer])
- [#3461] Do not reset the entire flash bag when resetting the message system ([leofeyer])
- [#3416] Dispatch the PreviewUrlCreateEvent even with an empty ID ([SeverinGloeckle])
- [#3467] Prevent image overflow for selected content elements ([fritzmg])
- [#3457] Fix the picker for child tables without PID ([ausi])
- [#3426] Migrate the newsletter module keys in version 4.0 ([aschempp])
- [#3462] Add a label for "edit multiple" to the deep link confirmation screen ([leofeyer])
- [#3460] Fix the position of the pagination menu in "edit multiple" mode ([leofeyer])
- [#3459] Fix a broken if-condition in tl_files::excludeFolder() ([leofeyer])
- [#3455] Handle flags in insertTagToSrc() ([ausi])
- [#3454] Fix the event endTime adjustment ([fritzmg])
- [#3444] Service priority 0 is not the same as not-set priority ([aschempp])
- [#3441] Add canonical name to routes ([aschempp])
- [#3329] Make sure X-Forwarded-Host is trusted if trusted hosts are configured ([Toflar])
- [#3422] Handle `%` characters in the SERP preview ([fritzmg])
- [#3407] Respect the `decodeEntities` flag with rgxp => url ([rabauss])
- [#3412] Do not dynamically disable HttpCache but rely on env vars instead ([Toflar])
- [#3423] Convert allowed attributes to lowercase ([ausi])
- [#3417] Use PHP functions to modify query strings ([leofeyer])
- [#3405] Harden the version 4.5.0 migration ([fritzmg])
- [#3394] Show table name in model relation exception ([SeverinGloeckle])
- [#3402] Fix the `Adapter::__call()` method signature ([SeverinGloeckle])
- [#3390] Fix simple token parsing in HTML ([ausi])
- [#3383] Fix the default quote style ([leofeyer])
- [#3296] Fix a phpDoc comment in the Pagination class ([zonky2])

## [4.9.20] (2021-08-24)

**Fixed issues:**

- [#3369] Fix using insert tags in the page title ([ausi])
- [#3360] Use intl for text direction ([fritzmg])
- [#3367] Handle insert tag flags in the picker ([leofeyer])
- [#3310] Add the "dry-run" and "format=ndjson" options to the migrate command ([ausi])
- [#3359] Fix the media element migration ([ausi])
- [#3346] Fix versioning condition in DC_Folder edit action ([ausi])
- [#3356] Make the metadata available in the download element ([leofeyer])
- [#3341] Clarify contao.preview_script usage ([fritzmg])
- [#3314] Add support for namespaced attributes ([ausi])
- [#3303] Warn if the DB server is not running in strict mode ([ausi])
- [#3321] Correctly explode foreign keys in the DCA ([fritzmg])
- [#3323] Remove reference to other table from column classname in DC_Table ([dennisbohn])
- [#3319] Correctly encode HTML comments, <script> and <style> tags ([ausi])
- [#3315] Do not encode special characters if no tags are allowed ([ausi])
- [#3311] Fix version creation for entries with dynamic ptable ([fritzmg])
- [#3278] Check if stopwatch is started before stopping ([bytehead])
- [#3283] Check if the indexer service exists when purging search tables ([bytehead])

## [4.9.19] (2021-08-12)

**Fixed issues:**

- [#3293] Do not show the urlattr flag for rgxp url fields ([ausi])
- [#3292] Improve compatibility with JSON in attributes ([ausi])
- [#3291] Fix the "iflng" and "ifnlng" insert tags ([ausi])
- [#3275] Fix missing tooltips for widget help text ([ausi])

## [4.9.18] (2021-08-11)

**Security fixes:**

- Prevent privilege escalation with the form generator ([CVE-2021-37627])
- Prevent PHP file inclusion via insert tags ([CVE-2021-37626])
- Prevent XSS via HTML attributes in the back end ([CVE-2021-35955])

## [4.9.17] (2021-08-04)

**New features:**

- [#2940] Show the Contao layout in the Symfony profiler ([aschempp])

**Fixed issues:**

- [#3256] Revert 'Lazy-load the `rootFallbackLanguage` property' ([leofeyer])
- [#3214] Support request tokens in Symfony forms ([ausi])
- [#3251] Harden literal insert tag replacement ([m-vo])
- [#3245] Fix a func_get_arg() value error ([ausi])
- [#3220] Correctly clean up left-over records in DCA mode 5 ([aschempp])
- [#3218] Do not start the session in the login module ([ausi])
- [#3197] Allow defining entities alongside DCA definitions ([m-vo])
- [#3190] Consider the robots.txt content in the SearchIndexSubscriber ([Toflar])
- [#3221] Remove the dev firewall ([aschempp])
- [#3217] Allow robots setting for redirect pages ([fritzmg])
- [#3210] Ensure the numberOfItems label does refer to items only ([Toflar])
- [#3216] Add Google Conversion Linker cookie to deny list ([ausi])
- [#3179] Backport support for namespaced DC drivers ([fritzmg])
- [#3174] Correctly handle form fields in DC_Folder in "editAll" mode ([leofeyer])
- [#3047] Update the YouTube options ([leofeyer])
- [#3158] Undeprecate the "importUser" hook ([bytehead])
- [#3125] Check if tstamp exists before hiding unsaved elements ([aschempp])
- [#3077] Correctly render showColumns in the picker widget ([aschempp])
- [#2958] Fix the tooltips in the JavaScript wizards ([rabauss])
- [#3085] Do not disable search and cache in FAQ and newsletter readers ([fritzmg])
- [#3083] Fix the indentation in the news_full template ([fritzmg])
- [#3078] Purge log and undo tables via cron ([Toflar])
- [#3067] Lazy-load the `rootFallbackLanguage` property ([aschempp])

## [4.9.16] (2021-06-23)

**Security fixes:**

- Prevent XSS in the system log ([CVE-2021-35210])

**Fixed issues:**

- [#3112] Use "anon." as username if authentication fails ([leofeyer])

## [4.9.15] (2021-06-08)

**New features:**

- [#3046] Add the "findCalendarBoundaries" hook ([leofeyer])
- [#2960] Protect users against Google FLoC ([aschempp])

**Fixed issues:**

- [#3058] Do not cast the picker values to integers ([leofeyer])
- [#3054] Fix a language typo ([Toflar])
- [#3043] Rename the close button at the bottom of the modal window ([leofeyer])
- [#3042] Handle non-string arguments in the `FilesModel::findByPath()` method ([leofeyer])
- [#3041] Use a better label for the password confirmation field ([leofeyer])
- [#2994] Hide unsaved content elements in the front end ([leofeyer])
- [#3028] Do not require ocramius/proxy-manager ([leofeyer])
- [#3007] Allow saving the value `0` in the KeyValueWizard ([doishub])
- [#3029] Do not modify the select name at runtime ([aschempp])
- [#2952] Set a label for the Contao Manager back end menu entry ([cliffparnitzky])
- [#2970] Load protected status from parent pages in customnav ([patrickjDE])
- [#3000] Simplify the latest DC_Table change ([leofeyer])
- [#2999] Remove two left-over autocomplete attributes ([leofeyer])

## [4.9.14] (2021-05-11)

**Fixed issues:**

- [#2996] Correctly strip the root path in the FilesModel and Dbafs classes ([leofeyer])
- [#2930] Add template properties to fragment proxy ([aschempp])
- [#2971] Do not render an empty custom navigation ([patrickjDE])
- [#2946] Fix the page context when generating RSS feeds ([fritzmg])
- [#2935] Fix the PageModel registry state ([ausi])
- [#2977] Fix external news targets not opening in a new window ([fritzmg])
- [#2989] Fix the favicon.ico route ([fritzmg])
- [#2967] Use the date formats from the page context for insert tags ([fritzmg])
- [#2894] Fix a normalize whitespace error in the DomCrawler ([fritzmg])
- [#2811] Add width/height attributes to the picture source ([ausi])
- [#2979] Fix a regression in the limit menu compilation ([bezin])
- [#2973] Backport the autocomplete changes ([leofeyer])
- [#2954] Do not start the session to check if a property exists ([aschempp])
- [#2957] Show zero values in the list view and DCA filters ([fritzmg])
- [#2878] Support "submitOnChange" in the FileTree widget ([AlexejKossmann])
- [#2965] Reset the bundle loader on kernel shutdown ([aschempp])
- [#2956] Show the values of unknown options ([cliffparnitzky])
- [#2948] Fix array to string conversion in the picker ([ausi])
- [#2945] Add the `--migrations-only` option to the migrate command ([ausi])
- [#2942] Do not count to check if there is a language file ([aschempp])
- [#2941] Remove the system log entry for 'no root page found' ([fritzmg])
- [#2933] Compile the limit menu after all other filter panels ([bezin])
- [#2906] Fix label callback for tree view in picker widget ([rabauss])
- [#2926] Use CSS to add the main headline separators ([leofeyer])
- [#2929] Fix compatibility with doctrine/dbal 2.13 ([ausi])
- [#2914] Reset invalid important part values in the version 4.8.0 migration ([ausi])
- [#2913] Fix the "runContextLength" migration when the previous migration has failed ([fritzmg])

## [4.9.13] (2021-03-24)

**Fixed issues:**

- [#2905] Fix service tagging for url_callback + title_tag_callback ([rabauss])
- [#2901] Respect _target_path on logout if set ([bytehead])
- [#2884] Fix the version 4.8.0 migration ([ausi])
- [#2899] Fix error when using return => Array for models ([fritzmg])
- [#2893] Fix an "undefined index: filesize" error in the indexer ([fritzmg])
- [#2883] Return a 404 status code if an image file does not exist ([ausi])
- [#2887] Add the Cookiebot Cookie Consent cookie to the cookie deny list ([MarkejN])
- [#2885] Remove a redundant strip cookie regex ([leofeyer])
- [#2877] Fix the UNIX_TIMESTAMP function in MySQL 8 ([leofeyer])
- [#2875] Fix two CSS issues in the back end ([leofeyer])
- [#2876] Adjust the Google Analytics strip cookie regex ([leofeyer])
- [#2762] Fix loading database.sql files in the DcaExtractor ([m-vo])
- [#2780] Handle session being null in System::getReferer ([m-vo])
- [#2864] Use the image template everywhere ([fritzmg])
- [#2861] Fix table content element not showing zeros ([fritzmg])
- [#2854] Respect the label field order in the picker ([bezin])
- [#2837] Use eval.context in the picker if set ([ausi])
- [#2836] Initialize the Contao framework in the Version480Update class ([leofeyer])
- [#2838] Adjust the description of the YouTube "rel" option ([leofeyer])
- [#2827] Fix generating error pages by name ([aschempp])
- [#2829] Start the session to check for a user token ([aschempp])
- [#2815] Do not tag contao.db.tl_module.<id> in articles ([m-vo])
- [#2831] Always sort root pages by language first ([aschempp])
- [#2674] Handle image URLs with a {{file::*}} insert tag in the lightbox ([fritzmg])
- [#2799] Include table views in the listing module drop-down menu ([fritzmg])
- [#2689] Follow redirects in the failure method of the Request.Contao class ([leofeyer])
- [#2735] Handle non-regex values in the search filter ([ausi])
- [#2777] Ignore empty authorization headers in the MakeResponsePrivateListener ([ausi])
- [#2800] Fix the json+ld schema extraction ([Toflar])
- [#2819] Sort root page routes after other page routes ([aschempp])
- [#2779] Allow 0 as default value for range sliders ([fritzmg])

## [4.9.12] (2021-02-16)

**Fixed issues:**

- [#2754] Fix the line-height of the main headline ([leofeyer])
- [#2755] Remove an unnecessary loadLanguageFile() call ([leofeyer])
- [#1909] Correctly handle custom default templates of fragments ([fritzmg])
- [#2721] Ignore custom templates in the back end ([fritzmg])
- [#2717] Use the chained router to find root pages ([aschempp])
- [#2747] Do not query for PIDs when building the breadcrumb of a File data container ([ausi])
- [#2737] Use the internal page title for the search index ([Toflar])
- [#2497] Re-use the tl_member.password field in ModuleCloseAccount ([bennyborn])
- [#2688] Fix a BC break in the AbstractFragmentController ([leofeyer])
- [#2683] Adjust the scheb/2fa-bundle integration ([bytehead])
- [#2685] Use the request attribute to determine preview mode ([aschempp])

## [4.9.11] (2021-01-21)

**Fixed issues:**

- [#2667] Ignore Monolog log files when rotating log files ([leofeyer])
- [#2669] Use a listener to reset custom templates ([aschempp])
- [#2668] Fix empty value for boolean type ([fritzmg])
- [#2666] Reset the custom template if the element type changes ([leofeyer])
- [#2653] Fix the comments cache tagging ([leofeyer])
- [#2656] Correctly format the search query time ([leofeyer])
- [#2657] Fix the search query if there are no keywords ([leofeyer])
- [#2664] Use the 2fa/* subpackages instead of scheb/2fa ([bytehead])
- [#2659] Handle ID URLs in the combiner ([leofeyer])
- [#2658] Do not translate the analytics template names ([leofeyer])
- [#2655] Improve rendering long titles and file names ([leofeyer])
- [#2654] Always pass the DC object to the toggleFeatured() method ([leofeyer])
- [#2636] Use the correct User-Agent request header in Escargot ([qzminski])
- [#2614] Fix the file manager performance with large non-image files ([fritzmg])
- [#2628] Use the token checker instead of FE_USER_LOGGED_IN constant ([fritzmg])
- [#2609] Correctly validate min and max values in text fields ([aschempp])
- [#2591] Retrieve the PageModel from the current request ([aschempp])
- [#2617] Upgrade scheb/2fa to version 5 (PHP 8 compatibility) ([bytehead])
- [#2615] Fix the news link markup ([fritzmg])
- [#2602] Update the CONTRIBUTORS.md file ([leofeyer])
- [#2588] Do not use the SQL default for empty values ([fritzmg])
- [#2581] Use the Symfony InvalidArgumentException in commands ([m-vo])

## [4.9.10] (2020-12-10)

**Fixed issues:**

- [#2551] Fix the cache tag invalidation ([leofeyer])
- [#2540] Correctly load the DCA labels ([aschempp])
- [#2550] Do not index preview URLs for searching ([leofeyer])
- [#2547] Fix the compatibility with scssphp 1.4 ([ausi])
- [#2545] Move migrations to the core bundle ([ausi])
- [#2527] Use a textarea for the image caption field ([Toflar])
- [#2521] Do not try to generate fragments for generated fragments ([aschempp])
- [#2506] Handle the global page model in fragments ([aschempp])
- [#2535] Add compatibility with PHP 8 ([leofeyer])
- [#2534] Backport the doctrine-cache-bundle changes ([leofeyer])
- [#2528] Increase the undo period ([Toflar])
- [#2522] Allow version 3 of toflar/psr6-symfony-http-cache-store ([Toflar])
- [#2509] Add compatibility with terminal42/escargot 1.0 ([ausi])
- [#2480] Correctly assign the CSS class in the newsletter subscribe module ([leofeyer])
- [#2479] Correctly handle falsey values when decoding entities ([leofeyer])
- [#2474] Correctly apply the CSS classes in the content module ([leofeyer])
- [#2473] Add a Cache-Control header to the back end response ([leofeyer])
- [#2463] Remove the hard dependency on PDO ([fritzmg])
- [#2465] Fix routing issue with multiple domains and languages ([aschempp])
- [#2321] Allow version 2 of the Doctrine bundle ([bytehead])
- [#2433] Do not use all:unset with the preview toolbar ([leofeyer])

## [4.9.9] (2020-10-20)

**Fixed issues:**

- [#2434] Correctly move root level pages to the top ([leofeyer])
- [#2430] Correctly generate the HTML module ([leofeyer])
- [#2417] Register globals in the fragments pass ([aschempp])
- [#2416] Remove the Content-Length header when modifying the response ([aschempp])

## [4.9.8] (2020-10-07)

**Fixed issues:**

- [#2403] Resolve private services in the ContaoCoreExtensionTest class ([leofeyer])
- [#2399] Remove the last username from the session after use ([ausi])
- [#2363] Reset the KEY_BLOCK_SIZE when migrating the MySQL engine and row format ([aschempp])
- [#2388] Ignore the logout URL if no user is present ([fritzmg])
- [#2376] Add a title tag callback to the SERP preview ([leofeyer])
- [#2361] Prevent using page aliases that could be page IDs ([leofeyer])
- [#2380] Fix the popup button padding ([leofeyer])
- [#2373] Use $this->imageHref in the image.html5 template ([leofeyer])
- [#2339] Optimize the check for inlined services ([aschempp])
- [#2366] Harden non-normalized file extension comparisons in the LegacyResizer ([m-vo])
- [#2369] Correctly check for numeric page IDs ([aschempp])
- [#2351] Override the size variable for the ce_player template ([fritzmg])
- [#2362] Correctly handle IDNA hostnames in the root page ([leofeyer])
- [#2345] Support legacy console scripts in the initialize.php ([aschempp])

## [4.9.7] (2020-09-25)

**Fixed issues:**

- [#2342] Fix entering 0 in the back end ([leofeyer])
- [#2343] Only use $dc->id in the protectFolder() method ([leofeyer])

## [4.9.6] (2020-09-24)

**Security fixes:**

- Prevent insert tag injection in forms ([CVE-2020-25768])

**New features:**

- [#2148] Add support for HTTP cache subscribers ([aschempp])

**Fixed issues:**

- [#2313] Fix the resize options priority in the PictureFactory class ([m-vo])
- [#2320] Do not prolong unconfirmed opt-in tokens ([leofeyer])
- [#2300] Do not use the default player size ([fritzmg])
- [#2290] Fix warnings and deprecations when running unit tests ([ausi])
- [#2294] Stop using == '' with regard to PHP 8 ([leofeyer])
- [#2252] Do not change the CSRF token cookie if the response is not successful ([fritzmg])
- [#2281] Update dependecies for PHP 8.0 compatibility ([ausi])
- [#2264] Do not try to index a page if the search indexer is disabled ([aschempp])
- [#2260] Do not use floorToMinute() in the PageModel::loadDetails() method ([leofeyer])
- [#2257] Only use floorToMinute() in DB queries ([leofeyer])
- [#2248] Fix a type error in the back end menu listener ([leofeyer])
- [#2244] Do not log 503 exceptions ([fritzmg])
- [#2221] Use a temporary status code to redirect to the language root ([leofeyer])
- [#2220] Simplify the tl_content header fields ([leofeyer])
- [#2219] Only update the comment notification URL in the front end ([leofeyer])
- [#2206] Use the scope matcher if an element renders differently in BE and FE ([leofeyer])
- [#2204] Only check the request token for master requests ([fritzmg])
- [#2208] Always load DotEnv files if they exist ([leofeyer])
- [#2200] Load the default labels in the loadDcaFiles() method ([fritzmg])
- [#2182] Catch exceptions to prevent the resize images command from failing ([ausi])
- [#2181] Add the assets URL to non-combined files ([ausi])
- [#2155] Support captcha input wrapped in DIV ([aschempp])
- [#2153] Use the class name as cache key in System::import() ([leofeyer])
- [#2120] Support multiple fragments on the same controller ([aschempp])
- [#2150] Fix the checkbox height on mobile devices ([leofeyer])

## [4.9.5] (2020-08-10)

**Fixed issues:**

- [#2139] Also invalidate the ptable cache tags in the DC_Table class ([leofeyer])
- [#2103] $this->ptable not available in the DataContainer class ([leofeyer])
- [#2122] Remove the Contao-Merge-Cache-Control header in the master request ([leofeyer])
- [#2121] Correctly show the default text form field template ([leofeyer])
- [#2118] Make cookies secure if the request is secure ([leofeyer])
- [#2115] Do not add empty CSS classes to the template ([fritzmg])
- [#2097] Use HTTP status code 303 instead of 307 for redirects ([leofeyer])
- [#2028] Allow new major versions of two third-party packages ([leofeyer])
- [#2074] Fix the order of the CSRF and the private response listener ([ausi])
- [#2091] Check if the username has been submitted in the registration module ([leofeyer])
- [#2087] Show the picker menu even if there is only one tab ([leofeyer])
- [#2088] Increase the z-index of the top menu overlay ([leofeyer])
- [#2086] Also indicate default templates in the custom template menu ([leofeyer])
- [#2089] Use the input event instead of the keyup event in the preview toolbar ([m-vo])
- [#2083] Update the Matomo tracking code ([leofeyer])
- [#2085] Fix the tl_user_group.stop help text ([leofeyer])
- [#2081] Correctly generate the news/events preview URL in multi-domain mode ([leofeyer])
- [#2077] Correctly show the front end preview bar for non-admin users ([leofeyer])
- [#2078] Always render the "go to front end" link without preview fragment ([leofeyer])
- [#2050] Harden the table options lookup in the Installer class ([m-vo])
- [#2066] Correctly handle empty manager config files ([aschempp])
- [#619] Fix a potential error if the URL has a percentage in it ([qzminski], [aschempp])
- [#2055] Change the JSON-LD type "RegularPage" to "Page" ([ausi])
- [#2057] Set the singleSRC flag for the Youtube/Vimeo splash screen ([m-vo])
- [#2056] Use expectExceptionMessage() for non-deprecations ([ausi])
- [#1486] Fix a memory leak in the resize images command ([ausi])
- [#2040] Remove redundant comments ([Toflar])
- [#2039] Add the missing cache invalidations ([Toflar])
- [#2032] Do not reorder existing DROP INDEX queries to the end ([ausi])
- [#1982] Add debugging information to the MakeResponsePrivateListener ([Toflar])
- [#2007] Require at least jQuery 3.5 ([leofeyer])
- [#2005] Fix the textarea height ([leofeyer])
- [#1991] Fix warning in SearchIndexSubscriberTest ([fritzmg])
- [#1988] Update terminal42/service-annotation-bundle ([aschempp])
- [#1978] Reset the preview toolbar styles ([aschempp])
- [#1966] Do not run migration if tl_image_size table is missing ([aschempp])
- [#1967] Allow ResourceFinder in autowiring ([aschempp])
- [#1952] Add the missing ContentModel annotations ([fritzmg])
- [#1950] Remove two left-over requirements ([leofeyer])
- [#1943] Revert 'Remove symfony/monolog-bundle dependency from functional tests' ([leofeyer])
- [#1942] Fix a wrong return value in the back end locale listener test ([leofeyer])
- [#1932] Improve the error message for unsupported image formats ([ausi])

## [4.9.4] (2020-07-09)

**Fixed issues:**

- [#1920] Fix the toggle visibility checks ([leofeyer])
- [#1894] Revert the $rootDir changes in the ContaoModuleBundle class ([leofeyer])
- [#1919] Revert the alphabetical sorting of the back end menu ([leofeyer])
- [#1903] Load the security bundle after the framework bundle ([baumannsven])
- [#1667] Add SCSS source maps in debug mode ([denniserdmann])
- [#1914] Remove the symfony/monolog-bundle dependency from functional tests ([bytehead])
- [#1908] Rename Piwik to Matomo and updated the tracking code ([rabauss])
- [#1865] Store the crawl logs in a unique subfolder per installation ([bohnmedia])
- [#1892] Fix the visibility of the EnvironmentTest::$projectDir property ([leofeyer])
- [#1891] Rename all occurrences of rootDir to projectDir ([aschempp])
- [#1754] Allow forcing a password change upon login in the contao:user:password command ([m-vo])
- [#1762] Remove the redirect status type from 401 and 403 pages ([fritzmg])
- [#1871] Reduce the file queries by preloading image models ([Toflar])
- [#1883] Enable framework.assets by default in Managed Edition ([fritzmg])
- [#1880] Let the user disable 2FA if it is enforced ([bytehead])
- [#1886] Increase the margin for TinyMCE fields ([fritzmg])
- [#1879] Fix the back end layout yet again ([fritzmg])
- [#1877] Fix the widget headline and help wizard alignment ([leofeyer])
- [#1875] Fix the search field height in the back end ([leofeyer])
- [#1844] Lazy-load commands ([aschempp])
- [#1823] Fix back end layout problems in various browsers ([fritzmg])
- [#1815] Show error 500 for unsupported image types ([ausi])
- [#1843] Added Tideways profiler cookie to the cookie deny list ([Toflar])
- [#1840] Improve the legacy class import performance ([Toflar])
- [#1839] Improve the performance of the file manager ([Toflar])
- [#1828] Correctly fix the mailer transport ([fritzmg])
- [#1827] Add compatibility with imagine-svg 1.0 ([ausi])
- [#1817] Ignore minlength/maxlength/minval/maxval in hidden fields ([qzminski])
- [#1763] Add the Osano Cookie Consent cookie to the cookie deny list ([Mynyx])
- [#1771] Replace "visitors" with "members" in the 2FA explanation ([Mynyx])
- [#1774] Fix addImageToTemplate with fullsize ([fritzmg])
- [#1788] Fix Escargot 0.6 compat and skip broken link checker ([Toflar])
- [#1583] Hide the crawler in maintenance mode ([leofeyer])
- [#1776] Correctly redirect to the preferred language if there is no index alias ([aschempp])
- [#1790] Ignore the Litespeed HTTP2 Smart Push cookie ([Toflar])
- [#1761] Use the createResult() method in CeAccessMigration ([fritzmg])

## [4.9.3] (2020-05-14)

**Fixed issues:**

- [#1745] Replace ocramius/package-versions with composer/package-versions-deprecated ([leofeyer])
- [#1742] Fix notices for empty database result sets ([ausi])
- [#1743] Correctly check for duplicate input parameters ([aschempp])
- [#1740] Rename "security question" to "spam protection" ([leofeyer])
- [#1699] Ignore minval/maxval in checkbox/radio/select fields ([aschempp])
- [#1738] Re-add the page ID to the JSON-LD context ([leofeyer])
- [#1729] Always show the default template in the drop-down menu ([leofeyer])
- [#1701] Improve the deprecation message of the AbstractLockedCommand ([Blog404DE])
- [#1733] Fix the indentation in the event_list.html5 template ([leofeyer])
- [#1732] Redirect if a news/event has an external target and is called via the default URL ([leofeyer])
- [#1727] Move the metadata fields back up in the news/events module ([leofeyer])
- [#1715] Re-add the redirect in the BackendUser::authenticate() method ([leofeyer])
- [#1712] Remove the broken storeFrontendReferer() method ([leofeyer])
- [#1711] Fix fixed position of toolbar elements ([fritzmg])
- [#1651] Execute schema diff queries in the correct order ([ausi])
- [#1694] Handle invalid language codes in the meta wizard ([leofeyer])
- [#1692] Skip the preview redirect if the preview script is not set ([leofeyer])
- [#1691] Add the translation domain in the installation controller ([leofeyer])
- [#1625] Fix the wrong CSRF token storage being wired ([Toflar])
- [#1628] Adjust the FrontendController::checkCookiesAction() comment ([Mynyx])
- [#1638] Correctly check if the search panel is active ([dmolineus])
- [#1658] Add the Contao Manager cookie to the cookie deny list ([Mynyx])
- [#1663] Fix the playerAspect default value ([fritzmg])
- [#1668] Fix running the broken link checker ([richardhj])
- [#1670] Fix the search indexer page detection ([qzminski])
- [#1673] Do not update the search index if contao itself is crawling ([Toflar])
- [#1642] Fix the tl_maintenance_jobs.crawl_queue explanation ([Mynyx])
- [#1640] Return the request argument if it has the correct type ([aschempp])
- [#1634] Verify TOTP with a window of 1 for better UX ([Toflar])
- [#1623] Also add z-index to the .cto-toolbar__open element ([leofeyer])
- [#1590] Change how to count records in RobotsTxtListener ([fritzmg])

## [4.9.2] (2020-04-02)

**Fixed issues:**

- [#1615] Add additional Google Analytics cookies to the cookie deny list ([Mynyx])
- [#1614] Fix a comment in the MakeResponsePrivateListener class ([Mynyx])
- [#1613] Fix the Contao toolbar labels ([leofeyer])
- [#1612] Ensure that the login icons are always visible in Firefox ([leofeyer])
- [#1608] Increase the split button breakpoint ([leofeyer])
- [#1600] Correctly filter subscriber specific crawl logs ([Toflar])
- [#1599] Fixed broken URIs not being reported as error in search index subscriber ([Toflar])
- [#1598] Hide the metadata fields if a news/event points to an external source ([leofeyer])
- [#1549] Dynamically configure the TokenChecker service ([bytehead])
- [#1592] Fix the picker in the meta wizard ([leofeyer])
- [#1596] Handle the "toggle nodes" command in the main back end method ([leofeyer])
- [#1597] Correctly check whether a group is allowed to import themes ([leofeyer])
- [#1542] Keep the sorting of the selected IDs for actions ([rabauss])
- [#1595] Use the correct page title on the back end dashboard page ([leofeyer])
- [#1593] Re-add the "edit meta" label in the news and calendar bundles ([leofeyer])
- [#1591] Show all files in the template editor ([leofeyer])
- [#1579] Fix the preview bar alignment ([leofeyer])
- [#1586] Correctly check for loaded languages when adding the default labels ([leofeyer])
- [#1584] Show "-" if the device family is "Other" ([leofeyer])
- [#1582] Remove the default preview bar datalist option ([leofeyer])
- [#1581] Disable the "switch user" button if it would impersonate the original user ([leofeyer])
- [#1580] Fix the tl_content.listtype DCA definition ([leofeyer])
- [#1554] Send the correct content type for SVG favicons ([Toflar])
- [#1551] Allow SVG images in favicons ([Toflar])
- [#1541] Show label instead of ID in the picker widget ([ausi])
- [#1525] Remove string type hint as there can be an array value ([bytehead])
- [#1182] Allow clearing the model registry ([m-vo])
- [#1534] Port the 'handle URL suffix when redirecting page IDs' changes ([leofeyer])
- [#1533] Correctly sort if both root pages are fallback ([aschempp])
- [#1532] Fixed some Piwik/Matomo cookie regex ([aschempp])
- [#1520] Optimize MSC.twoFactorBackupCodesExplain ([Mynyx])
- [#1513] Fix a "toggle element" permission check ([rabauss])
- [#1493] Adjust the JSON-LD data in the default indexer test ([leofeyer])
- [#1475] Register custom types in functional tests ([aschempp])
- [#1437] Translate the "show preview toolbar" title ([richardhj])
- [#1457] Use a context prefix in the JSON-LD schema ([ausi])
- [#1455] Do not expose the page ID in the JSON-LD context ([Toflar])
- [#1450] Use the native font stack in the layout.html.twig template ([fritzmg])
- [#1439] Fix missing image sizes with numeric theme names ([ausi])
- [#1444] Make sure log messages are in proper CSV format ([Toflar])
- [#1445] Retry failed schema diff migrations ([ausi])
- [#1453] Fix the input length of the alias fields ([aschempp])

## [4.9.1] (2020-02-27)

**Fixed issues:**

- [#1423] Revert the document.write() changes ([leofeyer])
- [#1420] Handle the "no JSON-LD found" case separately from the "noSearch" case ([leofeyer])
- [#1421] Update the composer run documentation in the README.md file ([leofeyer])
- [#1411] Skip orphan pages in the route provider ([aschempp])
- [#1419] Always append the current URL on redirect ([aschempp])
- [#1416] Correctly check if a folder has been renamed ([leofeyer])
- [#1417] Fix uploading files into mounted folders for regular users ([leofeyer])
- [#1418] Add a better DNS check in the site structure ([leofeyer])
- [#1413] Correctly calculate the crawler progress ([leofeyer])
- [#1396] Do not show the current URI in the progress bar title when crawling ([Toflar])
- [#1385] Warn if the crawler runs without a domain name ([Toflar])
- [#1369] Clear the dev cache in the script handler ([Toflar])
- [#1370] Make sure the subdirectory in the tmp folder exists ([Toflar])
- [#1377] Fix the getAttributesFromDca() type hint ([leofeyer])
- [#1376] Adjust the login screen again ([leofeyer])
- [#1375] Simulate active state of the debug button in debug mode ([leofeyer])
- [#1374] Show the correct help text for the 2FA verification field ([leofeyer])
- [#1373] Fix the Ajax visibility toggle in the site structure ([leofeyer])
- [#1372] Correctly save new template folders ([leofeyer])
- [#1359] Fix the PictureFactoryInterface::create() type hint ([bytehead])
- [#1364] Fix the login screen CSS ([leofeyer])
- [#1354] Hide the 2FA fields in the back end info modal ([bytehead])

## [4.9.0] (2020-02-18)

**Fixed issues:**

- [#1348] Correctly align the wizard icon ([leofeyer])
- [#1336] Make the contao.search.indexer service public ([leofeyer])
- [#1250] Use a custom schema for the search indexing metadata ([Toflar])
- [#1335] Correctly highlight phrases in the search results ([leofeyer])
- [#1323] Adjust the SERP widget to the Google search results ([leofeyer])
- [#1299] Fix several trusted device issues ([bytehead])
- [#1327] Fix rendering the picker preview ([leofeyer])
- [#1324] Sort the back end menu items alphabetically by label ([leofeyer])
- [#1322] Clear trusted devices when disabling 2FA ([bytehead])
- [#1320] Replace "recovery codes" with "backup codes" ([leofeyer])
- [#1295] Handle removed search indexers ([Toflar])

## [4.9.0-RC2] (2020-02-11)

**Fixed issues:**

- [#1292] Revert the "contao_backend_switch" route name change ([richardhj])
- [#1293] Update the back end keyboard shortcuts link ([leofeyer])
- [#1291] Hide the member widget if searching protected sites is disabled ([leofeyer])
- [#1286] Fix the front end preview URLs in the back end ([leofeyer])
- [#1267] Fix possible null value in AuthenticationSuccessHandler ([bytehead])
- [#1275] Move the PreviewAuthenticationListener to the core-bundle ([bytehead])
- [#1257] Allow DCA filter definitions without placeholder values ([fritzmg])
- [#1289] Improve the post update/install CLI hint ([Toflar])
- [#1282] Correctly publish new folders ([leofeyer])
- [#1283] Use wikimedia/less.php instead of oyejorge/less.php ([leofeyer])
- [#1284] Add z-index:1 to the paste hint ([leofeyer])
- [#1230] Add a "title" attribute to the debug mode menu item ([xchs])
- [#1255] Do not use a relative font for the preview bar ([leofeyer])
- [#1222] Do not try to register Contao 3 classes as services ([aschempp])
- [#1246] Fix possible null value access in BackupCodeManager ([bytehead])
- [#1237] Set the DB password to null if empty ([leofeyer])
- [#1217] Correctly encode the DATABASE_URL parameter ([richardhj])
- [#1232] Add the "url" parameter to the DropZone options ([bytehead])
- [#1225] Correctly determine the Ajax URL now that our forms no longer have an action ([leofeyer])
- [#1223] Add a crawl queue maintenance job ([Toflar])
- [#1221] Set the row format in the default table options ([leofeyer])

## [4.9.0-RC1] (2020-01-18)

**New features:**

- [#559] Add trusted devices for 2FA ([bytehead])
- [#1165] Automatically load services in the src/ directory ([aschempp])
- [#1184] Add backup code functionality for 2FA ([bytehead], [aschempp])
- [#1098] Add a cron service and command with cron expressions ([fritzmg])
- [#1180] Use spl_object_id() instead of spl_object_hash() ([Toflar])
- [#1178] Rebuild the login process ([bytehead], [aschempp])
- [#1057] Add a back end maintenance task for the crawler ([Toflar])
- [#580] Get pagetree picker url parameters via own method ([rabauss])
- [#579] Get filetree picker url parameters via own method ([rabauss])
- [#521] Add a range field and support steps in text fields ([fritzmg])
- [#1154] Get universal picker url parameters via own method ([rabauss])
- [#989] Rework the front end preview ([richardhj])
- [#709] Add a migrations command ([ausi])
- [#581] Compile navigation row via own method ([rabauss])
- [#860] Use the Knp menu for the back end header menu ([leofeyer])
- [#1068] Implement a broken link checker ([Toflar])
- [#1132] Turn event listeners with more than one method into to subscribers ([leofeyer])
- [#705] Allow to limit the content element and form field types ([leofeyer])
- [#1121] Pass the module model to the navigation templates ([leofeyer])
- [#1125] Simplify the event registration ([leofeyer])
- [#1129] Update the node modules and run the Gulp task ([leofeyer])
- [#1122] Add the WEBP icon to the mimetypes mapper ([leofeyer])
- [#1123] Do not strip forms in the ModuleArticle::generatePdf() method ([leofeyer])
- [#1124] Rename "account settings" to "group settings" for groups ([leofeyer])
- [#1115] Test the service arguments more accurately ([leofeyer])
- [#1101] Refactor the back end main menu, so it becomes a regular Knp menu ([leofeyer])
- [#714] Add a universal table picker ([aschempp])
- [#1085] Add support for the new bundle structure ([aschempp])
- [#1078] Correctly reset the necessary services ([aschempp])
- [#1094] Upgrade to PHPStan 0.12 ([leofeyer])
- [#1080] Replace Guzzle with Symfony's HttpClient ([Toflar])
- [#1086] Ignore the .github folder when installing from dist ([leofeyer])
- [#718] Use the cache strategy to merge fragment caching into the main page ([aschempp])
- [#1063] Add support for invokable listeners and method validation ([aschempp])
- [#603] Add an abstract controller for common service tasks ([aschempp])
- [#1055] Hide the metadata field when editing folders ([leofeyer])
- [#1045] Add @internal to what is not covered by our BC promise ([leofeyer])
- [#1053] Do not add the X-Forwarded-Host in Environment::url() anymore ([leofeyer])
- [#1058] Do not use Chosen in the meta wizard anymore ([leofeyer])
- [#1056] Cleanup the SubscriberResult class ([Toflar])
- [#1052] Add referrerpolicy="no-referrer" when loading assets via CDN ([leofeyer])
- [#954] Set the "accept" attribute in the upload form field ([bohnmedia])
- [#985] Implement the contao:crawl command ([Toflar])
- [#852] Add a command to debug fragments ([aschempp])
- [#1034] Allow to configure the HttpCache trace level using an environment variable ([Toflar])
- [#1039] Use the object type instead of @param object now that we have PHP 7.2 ([leofeyer])
- [#1027] Automatically upgrade new password hashes ([Toflar])
- [#983] Support configuring the SearchIndexListener behaviour ([Toflar])
- [#621] Add a command to debug manager plugins ([aschempp])
- [#1012] Update to Symfony 4.4 ([leofeyer])
- [#990] Explicitly enable the required PHP extensions ([leofeyer])
- [#976] Replace the ./run script with Composer ([leofeyer])
- [#968] Adjust the help text of the start/stop fields ([leofeyer])
- [#840] Use the default template path ([m-vo])
- [#955] Do not require league/uri anymore ([leofeyer])
- [#639] Fix model relations when using entities ([Tastaturberuf])
- [#946] Stop using the deprecated Doctrine DBAL methods ([leofeyer])
- [#948] Remove the app folder if there are no more files in it ([leofeyer])
- [#887] Clear invalid URLs using the new search indexer abstraction ([Toflar])
- [#945] Remove tests that are now always skipped ([leofeyer])
- [#943] Update to PHP 7.2, PHPUnit 8 and Doctrine DBAL 2.10 ([leofeyer])
- [#810] Automatically load the services.yml file if it exists ([leofeyer])
- [#730] Implement a search indexer abstraction ([Toflar])
- [#604] Pass the mime type to the download element links ([Toflar])
- [#672] Optimize DBAFS file sync ([m-vo])
- [#768] Support using env(DATABASE_URL) ([leofeyer])
- [#762] Do not install the tests with "prefer-dist" ([leofeyer])
- [#776] Simplify registering custom fragment types ([aschempp])
- [#717] Dynamically add robots.txt and favicon.ico per root page ([Toflar])
- [#703] Add support for lazy loading images ([ausi])

**Fixed issues:**

- [#1212] Tag the old version update classes as migrations ([leofeyer])
- [#1213] Change the allowed status codes for preview bar injection ([richardhj])
- [#1210] Show preview toolbar on error pages ([richardhj])
- [#1211] Fix issues found by the PhpStorm code inspector ([leofeyer])
- [#1209] Refine the new labels ([leofeyer])
- [#806] Add info for more configuration options ([fritzmg])
- [#1199] Support .yaml config files and the routes.yaml file ([aschempp])
- [#1201] Remove all form actions ([aschempp])
- [#1203] Improve the clickable area of the fieldset legends ([leofeyer])
- [#1177] Toggle DCA legends on their legend text only ([Tastaturberuf])
- [#1196] Warn if a user or user group has permission to import themes ([leofeyer])
- [#1197] Rework the module labels ([leofeyer])
- [#1202] Always allow root pages in custom navigation modules ([leofeyer])
- [#1195] Use the two factor factory constants in the unit tests ([leofeyer])
- [#1189] Fix the security.yml code in the README file ([bytehead])
- [#1190] Do not merge sub-requests without Cache-Control header ([aschempp])
- [#1191] Do not pollute the session with a target path ([aschempp])
- [#1194] Show the correct error message if a wrong 2FA token is entered ([bytehead])
- [#1188] Remove a superfluous argument in the RememberMeRepository class ([fritzmg])
- [#1181] Always set the connection in the RememberMeRepository class ([leofeyer])
- [#1173] Generate all languages when warming the cache ([leofeyer])
- [#1179] Do not decode entities in page names ([leofeyer])
- [#1175] Add the ce_access database migration ([leofeyer])
- [#1176] Disable the MakeResponsePrivateListener for non-Contao requests ([Toflar])
- [#1048] Improve the "purge search results cache" label ([Toflar])
- [#1171] Clean up the Escargot integration ([Toflar])
- [#1118] Override the authentication listener to validate FORM_SUBMIT ([aschempp])
- [#1169] Fix the SERP preview alias field and missing primary key ([aschempp])
- [#1164] Redirect to FE preview page after login and ensure integrity of target links ([Toflar])
- [#1162] Remove the obsolete container.autowiring.strict_mode parameter ([aschempp])
- [#1159] Fix the contao.image.sizes keys ([leofeyer])
- [#1158] Use addArgument() and addOption() instead of setDefinition() ([leofeyer])
- [#1155] Fix parameter names which are not in snake case ([leofeyer])
- [#1157] Update the badges in the README.md file ([leofeyer])
- [#1153] Fix the user and user group filters ([leofeyer])
- [#1151] Fix the description of the contao:crawl command ([leofeyer])
- [#1149] Fix the tl_files.source label ([leofeyer])
- [#1150] Rename "Column" to "Layout section" ([leofeyer])
- [#1148] Rename "File location" to "Path" ([leofeyer])
- [#1147] Add "contao.editable_files" to the configuration ([leofeyer])
- [#1131] Fix a method name in the TwoFactorFrontendListenerTest class ([bytehead])
- [#1128] Use the version callbacks to back up and restore file contents ([leofeyer])
- [#1126] Remove the registerCommands() methods ([leofeyer])
- [#1092] Fix the SERP preview ([leofeyer])
- [#1120] Only check $this->admin in the BackendUser class ([leofeyer])
- [#1116] Replace "web/" with "contao.web_dir" ([leofeyer])
- [#1113] Fix the search focus outline in Safari ([leofeyer])
- [#1114] Fix a typo in the FrontendTemplate class ([leofeyer])
- [#1100] Do not use array_insert to inject modules and menu items ([leofeyer])
- [#1102] Make sure we have the correct type when a search document is created ([Toflar])
- [#1095] Also test if the number of service tags matches ([leofeyer])
- [#1097] Clean up the Composer conflicts ([leofeyer])
- [#1054] Fix the page type descriptions ([leofeyer])
- [#1046] Fix the height of the meta wizard button ([leofeyer])
- [#1050] Use Throwable instead of Exception in the exception and error listeners ([leofeyer])
- [#1033] Disable auto cache control of the Symfony SessionListener ([Toflar])
- [#1013] Check the attribute type the BackendAccessVoter::supports() method ([AndreasA])
- [#1010] Fix the Doctrine platform recognition ([leofeyer])
- [#991] Replace mb_strlen() with Utf8::strlen() ([leofeyer])

[Semantic Versioning]: https://semver.org/spec/v2.0.0.html
[4.9.33]: https://github.com/contao/contao/releases/tag/4.9.33
[4.9.32]: https://github.com/contao/contao/releases/tag/4.9.32
[4.9.31]: https://github.com/contao/contao/releases/tag/4.9.31
[4.9.30]: https://github.com/contao/contao/releases/tag/4.9.30
[4.9.29]: https://github.com/contao/contao/releases/tag/4.9.29
[4.9.28]: https://github.com/contao/contao/releases/tag/4.9.28
[4.9.27]: https://github.com/contao/contao/releases/tag/4.9.27
[4.9.26]: https://github.com/contao/contao/releases/tag/4.9.26
[4.9.25]: https://github.com/contao/contao/releases/tag/4.9.25
[4.9.24]: https://github.com/contao/contao/releases/tag/4.9.24
[4.9.23]: https://github.com/contao/contao/releases/tag/4.9.23
[4.9.22]: https://github.com/contao/contao/releases/tag/4.9.22
[4.9.21]: https://github.com/contao/contao/releases/tag/4.9.21
[4.9.20]: https://github.com/contao/contao/releases/tag/4.9.20
[4.9.19]: https://github.com/contao/contao/releases/tag/4.9.19
[4.9.18]: https://github.com/contao/contao/releases/tag/4.9.18
[4.9.17]: https://github.com/contao/contao/releases/tag/4.9.17
[4.9.16]: https://github.com/contao/contao/releases/tag/4.9.16
[4.9.15]: https://github.com/contao/contao/releases/tag/4.9.15
[4.9.14]: https://github.com/contao/contao/releases/tag/4.9.14
[4.9.13]: https://github.com/contao/contao/releases/tag/4.9.13
[4.9.12]: https://github.com/contao/contao/releases/tag/4.9.12
[4.9.11]: https://github.com/contao/contao/releases/tag/4.9.11
[4.9.10]: https://github.com/contao/contao/releases/tag/4.9.10
[4.9.9]: https://github.com/contao/contao/releases/tag/4.9.9
[4.9.8]: https://github.com/contao/contao/releases/tag/4.9.8
[4.9.7]: https://github.com/contao/contao/releases/tag/4.9.7
[4.9.6]: https://github.com/contao/contao/releases/tag/4.9.6
[4.9.5]: https://github.com/contao/contao/releases/tag/4.9.5
[4.9.4]: https://github.com/contao/contao/releases/tag/4.9.4
[4.9.3]: https://github.com/contao/contao/releases/tag/4.9.3
[4.9.2]: https://github.com/contao/contao/releases/tag/4.9.2
[4.9.1]: https://github.com/contao/contao/releases/tag/4.9.1
[4.9.0]: https://github.com/contao/contao/releases/tag/4.9.0
[4.9.0-RC2]: https://github.com/contao/contao/releases/tag/4.9.0-RC2
[4.9.0-RC1]: https://github.com/contao/contao/releases/tag/4.9.0-RC1
[CVE-2021-37627]: https://github.com/contao/contao/security/advisories/GHSA-hq5m-mqmx-fw6m
[CVE-2021-37626]: https://github.com/contao/contao/security/advisories/GHSA-r6mv-ppjc-4hgr
[CVE-2021-35955]: https://github.com/contao/contao/security/advisories/GHSA-hr3h-x6gq-rqcp
[CVE-2021-35210]: https://github.com/contao/contao/security/advisories/GHSA-h58v-c6rf-g9f7
[CVE-2020-25768]: https://github.com/contao/contao/security/advisories/GHSA-f7wm-x4gw-6m23
[AlexejKossmann]: https://github.com/AlexejKossmann
[ameotoko]: https://github.com/ameotoko
[AndreasA]: https://github.com/AndreasA
[aschempp]: https://github.com/aschempp
[ausi]: https://github.com/ausi
[baumannsven]: https://github.com/baumannsven
[bennyborn]: https://github.com/bennyborn
[bezin]: https://github.com/bezin
[Blog404DE]: https://github.com/Blog404DE
[bohnmedia]: https://github.com/bohnmedia
[bytehead]: https://github.com/bytehead
[cliffparnitzky]: https://github.com/cliffparnitzky
[dennisbohn]: https://github.com/dennisbohn
[denniserdmann]: https://github.com/denniserdmann
[dmolineus]: https://github.com/dmolineus
[doishub]: https://github.com/doishub
[fritzmg]: https://github.com/fritzmg
[leofeyer]: https://github.com/leofeyer
[leonexcc]: https://github.com/leonexcc
[m-vo]: https://github.com/m-vo
[MarkejN]: https://github.com/MarkejN
[Mynyx]: https://github.com/Mynyx
[patrickjDE]: https://github.com/patrickjDE
[qzminski]: https://github.com/qzminski
[rabauss]: https://github.com/rabauss
[richardhj]: https://github.com/richardhj
[SeverinGloeckle]: https://github.com/SeverinGloeckle
[Tastaturberuf]: https://github.com/Tastaturberuf
[Toflar]: https://github.com/Toflar
[xchs]: https://github.com/xchs
[xprojects-de]: https://github.com/xprojects-de
[zoglo]: https://github.com/zoglo
[zonky2]: https://github.com/zonky2
[#5013]: https://github.com/contao/contao/pull/5013
[#5164]: https://github.com/contao/contao/pull/5164
[#5130]: https://github.com/contao/contao/pull/5130
[#5155]: https://github.com/contao/contao/pull/5155
[#5143]: https://github.com/contao/contao/pull/5143
[#5132]: https://github.com/contao/contao/pull/5132
[#5154]: https://github.com/contao/contao/pull/5154
[#5145]: https://github.com/contao/contao/pull/5145
[#5123]: https://github.com/contao/contao/pull/5123
[#5121]: https://github.com/contao/contao/pull/5121
[#4628]: https://github.com/contao/contao/pull/4628
[#4887]: https://github.com/contao/contao/pull/4887
[#5072]: https://github.com/contao/contao/pull/5072
[#5101]: https://github.com/contao/contao/pull/5101
[#4777]: https://github.com/contao/contao/pull/4777
[#5075]: https://github.com/contao/contao/pull/5075
[#4968]: https://github.com/contao/contao/pull/4968
[#5032]: https://github.com/contao/contao/pull/5032
[#5077]: https://github.com/contao/contao/pull/5077
[#5010]: https://github.com/contao/contao/pull/5010
[#5102]: https://github.com/contao/contao/pull/5102
[#5058]: https://github.com/contao/contao/pull/5058
[#4964]: https://github.com/contao/contao/pull/4964
[#4975]: https://github.com/contao/contao/pull/4975
[#4966]: https://github.com/contao/contao/pull/4966
[#4366]: https://github.com/contao/contao/pull/4366
[#4895]: https://github.com/contao/contao/pull/4895
[#4865]: https://github.com/contao/contao/pull/4865
[#4864]: https://github.com/contao/contao/pull/4864
[#4906]: https://github.com/contao/contao/pull/4906
[#4894]: https://github.com/contao/contao/pull/4894
[#4880]: https://github.com/contao/contao/pull/4880
[#4867]: https://github.com/contao/contao/pull/4867
[#4879]: https://github.com/contao/contao/pull/4879
[#4858]: https://github.com/contao/contao/pull/4858
[#4853]: https://github.com/contao/contao/pull/4853
[#4845]: https://github.com/contao/contao/pull/4845
[#4828]: https://github.com/contao/contao/pull/4828
[#4832]: https://github.com/contao/contao/pull/4832
[#4574]: https://github.com/contao/contao/pull/4574
[#4819]: https://github.com/contao/contao/pull/4819
[#4809]: https://github.com/contao/contao/pull/4809
[#4814]: https://github.com/contao/contao/pull/4814
[#4804]: https://github.com/contao/contao/pull/4804
[#4802]: https://github.com/contao/contao/pull/4802
[#4766]: https://github.com/contao/contao/pull/4766
[#4711]: https://github.com/contao/contao/pull/4711
[#4718]: https://github.com/contao/contao/pull/4718
[#4708]: https://github.com/contao/contao/pull/4708
[#4727]: https://github.com/contao/contao/pull/4727
[#3742]: https://github.com/contao/contao/pull/3742
[#4690]: https://github.com/contao/contao/pull/4690
[#4698]: https://github.com/contao/contao/pull/4698
[#4686]: https://github.com/contao/contao/pull/4686
[#4693]: https://github.com/contao/contao/pull/4693
[#4680]: https://github.com/contao/contao/pull/4680
[#4670]: https://github.com/contao/contao/pull/4670
[#4633]: https://github.com/contao/contao/pull/4633
[#4647]: https://github.com/contao/contao/pull/4647
[#4638]: https://github.com/contao/contao/pull/4638
[#4622]: https://github.com/contao/contao/pull/4622
[#4621]: https://github.com/contao/contao/pull/4621
[#4519]: https://github.com/contao/contao/pull/4519
[#3711]: https://github.com/contao/contao/pull/3711
[#4605]: https://github.com/contao/contao/pull/4605
[#3887]: https://github.com/contao/contao/pull/3887
[#4596]: https://github.com/contao/contao/pull/4596
[#4595]: https://github.com/contao/contao/pull/4595
[#4590]: https://github.com/contao/contao/pull/4590
[#4586]: https://github.com/contao/contao/pull/4586
[#4588]: https://github.com/contao/contao/pull/4588
[#4497]: https://github.com/contao/contao/pull/4497
[#4500]: https://github.com/contao/contao/pull/4500
[#4477]: https://github.com/contao/contao/pull/4477
[#4463]: https://github.com/contao/contao/pull/4463
[#4570]: https://github.com/contao/contao/pull/4570
[#4481]: https://github.com/contao/contao/pull/4481
[#4528]: https://github.com/contao/contao/pull/4528
[#4195]: https://github.com/contao/contao/pull/4195
[#4470]: https://github.com/contao/contao/pull/4470
[#4493]: https://github.com/contao/contao/pull/4493
[#4509]: https://github.com/contao/contao/pull/4509
[#4487]: https://github.com/contao/contao/pull/4487
[#4192]: https://github.com/contao/contao/pull/4192
[#4320]: https://github.com/contao/contao/pull/4320
[#4455]: https://github.com/contao/contao/pull/4455
[#4427]: https://github.com/contao/contao/pull/4427
[#4424]: https://github.com/contao/contao/pull/4424
[#4423]: https://github.com/contao/contao/pull/4423
[#4219]: https://github.com/contao/contao/pull/4219
[#4408]: https://github.com/contao/contao/pull/4408
[#4252]: https://github.com/contao/contao/pull/4252
[#4402]: https://github.com/contao/contao/pull/4402
[#4357]: https://github.com/contao/contao/pull/4357
[#4294]: https://github.com/contao/contao/pull/4294
[#4331]: https://github.com/contao/contao/pull/4331
[#4293]: https://github.com/contao/contao/pull/4293
[#4299]: https://github.com/contao/contao/pull/4299
[#4263]: https://github.com/contao/contao/pull/4263
[#4256]: https://github.com/contao/contao/pull/4256
[#4236]: https://github.com/contao/contao/pull/4236
[#4373]: https://github.com/contao/contao/pull/4373
[#4322]: https://github.com/contao/contao/pull/4322
[#4266]: https://github.com/contao/contao/pull/4266
[#4285]: https://github.com/contao/contao/pull/4285
[#4248]: https://github.com/contao/contao/pull/4248
[#4241]: https://github.com/contao/contao/pull/4241
[#4235]: https://github.com/contao/contao/pull/4235
[#4237]: https://github.com/contao/contao/pull/4237
[#4233]: https://github.com/contao/contao/pull/4233
[#4234]: https://github.com/contao/contao/pull/4234
[#4232]: https://github.com/contao/contao/pull/4232
[#4231]: https://github.com/contao/contao/pull/4231
[#4229]: https://github.com/contao/contao/pull/4229
[#4150]: https://github.com/contao/contao/pull/4150
[#4091]: https://github.com/contao/contao/pull/4091
[#4199]: https://github.com/contao/contao/pull/4199
[#4212]: https://github.com/contao/contao/pull/4212
[#4135]: https://github.com/contao/contao/pull/4135
[#4218]: https://github.com/contao/contao/pull/4218
[#4225]: https://github.com/contao/contao/pull/4225
[#4227]: https://github.com/contao/contao/pull/4227
[#4226]: https://github.com/contao/contao/pull/4226
[#4210]: https://github.com/contao/contao/pull/4210
[#4204]: https://github.com/contao/contao/pull/4204
[#4155]: https://github.com/contao/contao/pull/4155
[#4083]: https://github.com/contao/contao/pull/4083
[#4105]: https://github.com/contao/contao/pull/4105
[#4045]: https://github.com/contao/contao/pull/4045
[#4075]: https://github.com/contao/contao/pull/4075
[#4072]: https://github.com/contao/contao/pull/4072
[#4067]: https://github.com/contao/contao/pull/4067
[#3997]: https://github.com/contao/contao/pull/3997
[#4056]: https://github.com/contao/contao/pull/4056
[#3857]: https://github.com/contao/contao/pull/3857
[#3800]: https://github.com/contao/contao/pull/3800
[#4009]: https://github.com/contao/contao/pull/4009
[#4031]: https://github.com/contao/contao/pull/4031
[#4020]: https://github.com/contao/contao/pull/4020
[#4013]: https://github.com/contao/contao/pull/4013
[#3984]: https://github.com/contao/contao/pull/3984
[#3971]: https://github.com/contao/contao/pull/3971
[#3981]: https://github.com/contao/contao/pull/3981
[#3790]: https://github.com/contao/contao/pull/3790
[#3488]: https://github.com/contao/contao/pull/3488
[#3910]: https://github.com/contao/contao/pull/3910
[#3915]: https://github.com/contao/contao/pull/3915
[#3842]: https://github.com/contao/contao/pull/3842
[#3966]: https://github.com/contao/contao/pull/3966
[#3939]: https://github.com/contao/contao/pull/3939
[#3876]: https://github.com/contao/contao/pull/3876
[#3882]: https://github.com/contao/contao/pull/3882
[#3877]: https://github.com/contao/contao/pull/3877
[#3836]: https://github.com/contao/contao/pull/3836
[#3867]: https://github.com/contao/contao/pull/3867
[#3841]: https://github.com/contao/contao/pull/3841
[#3822]: https://github.com/contao/contao/pull/3822
[#3815]: https://github.com/contao/contao/pull/3815
[#3803]: https://github.com/contao/contao/pull/3803
[#3792]: https://github.com/contao/contao/pull/3792
[#3788]: https://github.com/contao/contao/pull/3788
[#3787]: https://github.com/contao/contao/pull/3787
[#3781]: https://github.com/contao/contao/pull/3781
[#3759]: https://github.com/contao/contao/pull/3759
[#3758]: https://github.com/contao/contao/pull/3758
[#3741]: https://github.com/contao/contao/pull/3741
[#3732]: https://github.com/contao/contao/pull/3732
[#3738]: https://github.com/contao/contao/pull/3738
[#3734]: https://github.com/contao/contao/pull/3734
[#3470]: https://github.com/contao/contao/pull/3470
[#3692]: https://github.com/contao/contao/pull/3692
[#3690]: https://github.com/contao/contao/pull/3690
[#3699]: https://github.com/contao/contao/pull/3699
[#3647]: https://github.com/contao/contao/pull/3647
[#3668]: https://github.com/contao/contao/pull/3668
[#3601]: https://github.com/contao/contao/pull/3601
[#3656]: https://github.com/contao/contao/pull/3656
[#3654]: https://github.com/contao/contao/pull/3654
[#3665]: https://github.com/contao/contao/pull/3665
[#3637]: https://github.com/contao/contao/pull/3637
[#3623]: https://github.com/contao/contao/pull/3623
[#3627]: https://github.com/contao/contao/pull/3627
[#3607]: https://github.com/contao/contao/pull/3607
[#3608]: https://github.com/contao/contao/pull/3608
[#3595]: https://github.com/contao/contao/pull/3595
[#3598]: https://github.com/contao/contao/pull/3598
[#3599]: https://github.com/contao/contao/pull/3599
[#3585]: https://github.com/contao/contao/pull/3585
[#3527]: https://github.com/contao/contao/pull/3527
[#3134]: https://github.com/contao/contao/pull/3134
[#3571]: https://github.com/contao/contao/pull/3571
[#3548]: https://github.com/contao/contao/pull/3548
[#3352]: https://github.com/contao/contao/pull/3352
[#3543]: https://github.com/contao/contao/pull/3543
[#3496]: https://github.com/contao/contao/pull/3496
[#3534]: https://github.com/contao/contao/pull/3534
[#3526]: https://github.com/contao/contao/pull/3526
[#3503]: https://github.com/contao/contao/pull/3503
[#3521]: https://github.com/contao/contao/pull/3521
[#3519]: https://github.com/contao/contao/pull/3519
[#3517]: https://github.com/contao/contao/pull/3517
[#3513]: https://github.com/contao/contao/pull/3513
[#3512]: https://github.com/contao/contao/pull/3512
[#3508]: https://github.com/contao/contao/pull/3508
[#3499]: https://github.com/contao/contao/pull/3499
[#3497]: https://github.com/contao/contao/pull/3497
[#3475]: https://github.com/contao/contao/pull/3475
[#3480]: https://github.com/contao/contao/pull/3480
[#3481]: https://github.com/contao/contao/pull/3481
[#3464]: https://github.com/contao/contao/pull/3464
[#3461]: https://github.com/contao/contao/pull/3461
[#3416]: https://github.com/contao/contao/pull/3416
[#3467]: https://github.com/contao/contao/pull/3467
[#3457]: https://github.com/contao/contao/pull/3457
[#3426]: https://github.com/contao/contao/pull/3426
[#3462]: https://github.com/contao/contao/pull/3462
[#3460]: https://github.com/contao/contao/pull/3460
[#3459]: https://github.com/contao/contao/pull/3459
[#3455]: https://github.com/contao/contao/pull/3455
[#3454]: https://github.com/contao/contao/pull/3454
[#3444]: https://github.com/contao/contao/pull/3444
[#3441]: https://github.com/contao/contao/pull/3441
[#3329]: https://github.com/contao/contao/pull/3329
[#3422]: https://github.com/contao/contao/pull/3422
[#3407]: https://github.com/contao/contao/pull/3407
[#3412]: https://github.com/contao/contao/pull/3412
[#3423]: https://github.com/contao/contao/pull/3423
[#3417]: https://github.com/contao/contao/pull/3417
[#3405]: https://github.com/contao/contao/pull/3405
[#3394]: https://github.com/contao/contao/pull/3394
[#3402]: https://github.com/contao/contao/pull/3402
[#3390]: https://github.com/contao/contao/pull/3390
[#3383]: https://github.com/contao/contao/pull/3383
[#3296]: https://github.com/contao/contao/pull/3296
[#3369]: https://github.com/contao/contao/pull/3369
[#3360]: https://github.com/contao/contao/pull/3360
[#3367]: https://github.com/contao/contao/pull/3367
[#3310]: https://github.com/contao/contao/pull/3310
[#3359]: https://github.com/contao/contao/pull/3359
[#3346]: https://github.com/contao/contao/pull/3346
[#3356]: https://github.com/contao/contao/pull/3356
[#3341]: https://github.com/contao/contao/pull/3341
[#3314]: https://github.com/contao/contao/pull/3314
[#3303]: https://github.com/contao/contao/pull/3303
[#3321]: https://github.com/contao/contao/pull/3321
[#3323]: https://github.com/contao/contao/pull/3323
[#3319]: https://github.com/contao/contao/pull/3319
[#3315]: https://github.com/contao/contao/pull/3315
[#3311]: https://github.com/contao/contao/pull/3311
[#3278]: https://github.com/contao/contao/pull/3278
[#3283]: https://github.com/contao/contao/pull/3283
[#3293]: https://github.com/contao/contao/pull/3293
[#3292]: https://github.com/contao/contao/pull/3292
[#3291]: https://github.com/contao/contao/pull/3291
[#3275]: https://github.com/contao/contao/pull/3275
[#2940]: https://github.com/contao/contao/pull/2940
[#3256]: https://github.com/contao/contao/pull/3256
[#3214]: https://github.com/contao/contao/pull/3214
[#3251]: https://github.com/contao/contao/pull/3251
[#3245]: https://github.com/contao/contao/pull/3245
[#3220]: https://github.com/contao/contao/pull/3220
[#3218]: https://github.com/contao/contao/pull/3218
[#3197]: https://github.com/contao/contao/pull/3197
[#3190]: https://github.com/contao/contao/pull/3190
[#3221]: https://github.com/contao/contao/pull/3221
[#3217]: https://github.com/contao/contao/pull/3217
[#3210]: https://github.com/contao/contao/pull/3210
[#3216]: https://github.com/contao/contao/pull/3216
[#3179]: https://github.com/contao/contao/pull/3179
[#3174]: https://github.com/contao/contao/pull/3174
[#3047]: https://github.com/contao/contao/pull/3047
[#3158]: https://github.com/contao/contao/pull/3158
[#3125]: https://github.com/contao/contao/pull/3125
[#3077]: https://github.com/contao/contao/pull/3077
[#2958]: https://github.com/contao/contao/pull/2958
[#3085]: https://github.com/contao/contao/pull/3085
[#3083]: https://github.com/contao/contao/pull/3083
[#3078]: https://github.com/contao/contao/pull/3078
[#3067]: https://github.com/contao/contao/pull/3067
[#3112]: https://github.com/contao/contao/pull/3112
[#3046]: https://github.com/contao/contao/pull/3046
[#2960]: https://github.com/contao/contao/pull/2960
[#3058]: https://github.com/contao/contao/pull/3058
[#3054]: https://github.com/contao/contao/pull/3054
[#3043]: https://github.com/contao/contao/pull/3043
[#3042]: https://github.com/contao/contao/pull/3042
[#3041]: https://github.com/contao/contao/pull/3041
[#2994]: https://github.com/contao/contao/pull/2994
[#3028]: https://github.com/contao/contao/pull/3028
[#3007]: https://github.com/contao/contao/pull/3007
[#3029]: https://github.com/contao/contao/pull/3029
[#2952]: https://github.com/contao/contao/pull/2952
[#2970]: https://github.com/contao/contao/pull/2970
[#3000]: https://github.com/contao/contao/pull/3000
[#2999]: https://github.com/contao/contao/pull/2999
[#2996]: https://github.com/contao/contao/pull/2996
[#2930]: https://github.com/contao/contao/pull/2930
[#2971]: https://github.com/contao/contao/pull/2971
[#2946]: https://github.com/contao/contao/pull/2946
[#2935]: https://github.com/contao/contao/pull/2935
[#2977]: https://github.com/contao/contao/pull/2977
[#2989]: https://github.com/contao/contao/pull/2989
[#2967]: https://github.com/contao/contao/pull/2967
[#2894]: https://github.com/contao/contao/pull/2894
[#2811]: https://github.com/contao/contao/pull/2811
[#2979]: https://github.com/contao/contao/pull/2979
[#2973]: https://github.com/contao/contao/pull/2973
[#2954]: https://github.com/contao/contao/pull/2954
[#2957]: https://github.com/contao/contao/pull/2957
[#2878]: https://github.com/contao/contao/pull/2878
[#2965]: https://github.com/contao/contao/pull/2965
[#2956]: https://github.com/contao/contao/pull/2956
[#2948]: https://github.com/contao/contao/pull/2948
[#2945]: https://github.com/contao/contao/pull/2945
[#2942]: https://github.com/contao/contao/pull/2942
[#2941]: https://github.com/contao/contao/pull/2941
[#2933]: https://github.com/contao/contao/pull/2933
[#2906]: https://github.com/contao/contao/pull/2906
[#2926]: https://github.com/contao/contao/pull/2926
[#2929]: https://github.com/contao/contao/pull/2929
[#2914]: https://github.com/contao/contao/pull/2914
[#2913]: https://github.com/contao/contao/pull/2913
[#2905]: https://github.com/contao/contao/pull/2905
[#2901]: https://github.com/contao/contao/pull/2901
[#2884]: https://github.com/contao/contao/pull/2884
[#2899]: https://github.com/contao/contao/pull/2899
[#2893]: https://github.com/contao/contao/pull/2893
[#2883]: https://github.com/contao/contao/pull/2883
[#2887]: https://github.com/contao/contao/pull/2887
[#2885]: https://github.com/contao/contao/pull/2885
[#2877]: https://github.com/contao/contao/pull/2877
[#2875]: https://github.com/contao/contao/pull/2875
[#2876]: https://github.com/contao/contao/pull/2876
[#2762]: https://github.com/contao/contao/pull/2762
[#2780]: https://github.com/contao/contao/pull/2780
[#2864]: https://github.com/contao/contao/pull/2864
[#2861]: https://github.com/contao/contao/pull/2861
[#2854]: https://github.com/contao/contao/pull/2854
[#2837]: https://github.com/contao/contao/pull/2837
[#2836]: https://github.com/contao/contao/pull/2836
[#2838]: https://github.com/contao/contao/pull/2838
[#2827]: https://github.com/contao/contao/pull/2827
[#2829]: https://github.com/contao/contao/pull/2829
[#2815]: https://github.com/contao/contao/pull/2815
[#2831]: https://github.com/contao/contao/pull/2831
[#2674]: https://github.com/contao/contao/pull/2674
[#2799]: https://github.com/contao/contao/pull/2799
[#2689]: https://github.com/contao/contao/pull/2689
[#2735]: https://github.com/contao/contao/pull/2735
[#2777]: https://github.com/contao/contao/pull/2777
[#2800]: https://github.com/contao/contao/pull/2800
[#2819]: https://github.com/contao/contao/pull/2819
[#2779]: https://github.com/contao/contao/pull/2779
[#2754]: https://github.com/contao/contao/pull/2754
[#2755]: https://github.com/contao/contao/pull/2755
[#1909]: https://github.com/contao/contao/pull/1909
[#2721]: https://github.com/contao/contao/pull/2721
[#2717]: https://github.com/contao/contao/pull/2717
[#2747]: https://github.com/contao/contao/pull/2747
[#2737]: https://github.com/contao/contao/pull/2737
[#2497]: https://github.com/contao/contao/pull/2497
[#2688]: https://github.com/contao/contao/pull/2688
[#2683]: https://github.com/contao/contao/pull/2683
[#2685]: https://github.com/contao/contao/pull/2685
[#2667]: https://github.com/contao/contao/pull/2667
[#2669]: https://github.com/contao/contao/pull/2669
[#2668]: https://github.com/contao/contao/pull/2668
[#2666]: https://github.com/contao/contao/pull/2666
[#2653]: https://github.com/contao/contao/pull/2653
[#2656]: https://github.com/contao/contao/pull/2656
[#2657]: https://github.com/contao/contao/pull/2657
[#2664]: https://github.com/contao/contao/pull/2664
[#2659]: https://github.com/contao/contao/pull/2659
[#2658]: https://github.com/contao/contao/pull/2658
[#2655]: https://github.com/contao/contao/pull/2655
[#2654]: https://github.com/contao/contao/pull/2654
[#2636]: https://github.com/contao/contao/pull/2636
[#2614]: https://github.com/contao/contao/pull/2614
[#2628]: https://github.com/contao/contao/pull/2628
[#2609]: https://github.com/contao/contao/pull/2609
[#2591]: https://github.com/contao/contao/pull/2591
[#2617]: https://github.com/contao/contao/pull/2617
[#2615]: https://github.com/contao/contao/pull/2615
[#2602]: https://github.com/contao/contao/pull/2602
[#2588]: https://github.com/contao/contao/pull/2588
[#2581]: https://github.com/contao/contao/pull/2581
[#2551]: https://github.com/contao/contao/pull/2551
[#2540]: https://github.com/contao/contao/pull/2540
[#2550]: https://github.com/contao/contao/pull/2550
[#2547]: https://github.com/contao/contao/pull/2547
[#2545]: https://github.com/contao/contao/pull/2545
[#2527]: https://github.com/contao/contao/pull/2527
[#2521]: https://github.com/contao/contao/pull/2521
[#2506]: https://github.com/contao/contao/pull/2506
[#2535]: https://github.com/contao/contao/pull/2535
[#2534]: https://github.com/contao/contao/pull/2534
[#2528]: https://github.com/contao/contao/pull/2528
[#2522]: https://github.com/contao/contao/pull/2522
[#2509]: https://github.com/contao/contao/pull/2509
[#2480]: https://github.com/contao/contao/pull/2480
[#2479]: https://github.com/contao/contao/pull/2479
[#2474]: https://github.com/contao/contao/pull/2474
[#2473]: https://github.com/contao/contao/pull/2473
[#2463]: https://github.com/contao/contao/pull/2463
[#2465]: https://github.com/contao/contao/pull/2465
[#2321]: https://github.com/contao/contao/pull/2321
[#2433]: https://github.com/contao/contao/pull/2433
[#2434]: https://github.com/contao/contao/pull/2434
[#2430]: https://github.com/contao/contao/pull/2430
[#2417]: https://github.com/contao/contao/pull/2417
[#2416]: https://github.com/contao/contao/pull/2416
[#2403]: https://github.com/contao/contao/pull/2403
[#2399]: https://github.com/contao/contao/pull/2399
[#2363]: https://github.com/contao/contao/pull/2363
[#2388]: https://github.com/contao/contao/pull/2388
[#2376]: https://github.com/contao/contao/pull/2376
[#2361]: https://github.com/contao/contao/pull/2361
[#2380]: https://github.com/contao/contao/pull/2380
[#2373]: https://github.com/contao/contao/pull/2373
[#2339]: https://github.com/contao/contao/pull/2339
[#2366]: https://github.com/contao/contao/pull/2366
[#2369]: https://github.com/contao/contao/pull/2369
[#2351]: https://github.com/contao/contao/pull/2351
[#2362]: https://github.com/contao/contao/pull/2362
[#2345]: https://github.com/contao/contao/pull/2345
[#2342]: https://github.com/contao/contao/pull/2342
[#2343]: https://github.com/contao/contao/pull/2343
[#2148]: https://github.com/contao/contao/pull/2148
[#2313]: https://github.com/contao/contao/pull/2313
[#2320]: https://github.com/contao/contao/pull/2320
[#2300]: https://github.com/contao/contao/pull/2300
[#2290]: https://github.com/contao/contao/pull/2290
[#2294]: https://github.com/contao/contao/pull/2294
[#2252]: https://github.com/contao/contao/pull/2252
[#2281]: https://github.com/contao/contao/pull/2281
[#2264]: https://github.com/contao/contao/pull/2264
[#2260]: https://github.com/contao/contao/pull/2260
[#2257]: https://github.com/contao/contao/pull/2257
[#2248]: https://github.com/contao/contao/pull/2248
[#2244]: https://github.com/contao/contao/pull/2244
[#2221]: https://github.com/contao/contao/pull/2221
[#2220]: https://github.com/contao/contao/pull/2220
[#2219]: https://github.com/contao/contao/pull/2219
[#2206]: https://github.com/contao/contao/pull/2206
[#2204]: https://github.com/contao/contao/pull/2204
[#2208]: https://github.com/contao/contao/pull/2208
[#2200]: https://github.com/contao/contao/pull/2200
[#2182]: https://github.com/contao/contao/pull/2182
[#2181]: https://github.com/contao/contao/pull/2181
[#2155]: https://github.com/contao/contao/pull/2155
[#2153]: https://github.com/contao/contao/pull/2153
[#2120]: https://github.com/contao/contao/pull/2120
[#2150]: https://github.com/contao/contao/pull/2150
[#2139]: https://github.com/contao/contao/pull/2139
[#2103]: https://github.com/contao/contao/pull/2103
[#2122]: https://github.com/contao/contao/pull/2122
[#2121]: https://github.com/contao/contao/pull/2121
[#2118]: https://github.com/contao/contao/pull/2118
[#2115]: https://github.com/contao/contao/pull/2115
[#2097]: https://github.com/contao/contao/pull/2097
[#2028]: https://github.com/contao/contao/pull/2028
[#2074]: https://github.com/contao/contao/pull/2074
[#2091]: https://github.com/contao/contao/pull/2091
[#2087]: https://github.com/contao/contao/pull/2087
[#2088]: https://github.com/contao/contao/pull/2088
[#2086]: https://github.com/contao/contao/pull/2086
[#2089]: https://github.com/contao/contao/pull/2089
[#2083]: https://github.com/contao/contao/pull/2083
[#2085]: https://github.com/contao/contao/pull/2085
[#2081]: https://github.com/contao/contao/pull/2081
[#2077]: https://github.com/contao/contao/pull/2077
[#2078]: https://github.com/contao/contao/pull/2078
[#2050]: https://github.com/contao/contao/pull/2050
[#2066]: https://github.com/contao/contao/pull/2066
[#619]: https://github.com/contao/contao/pull/619
[#2055]: https://github.com/contao/contao/pull/2055
[#2057]: https://github.com/contao/contao/pull/2057
[#2056]: https://github.com/contao/contao/pull/2056
[#1486]: https://github.com/contao/contao/pull/1486
[#2040]: https://github.com/contao/contao/pull/2040
[#2039]: https://github.com/contao/contao/pull/2039
[#2032]: https://github.com/contao/contao/pull/2032
[#1982]: https://github.com/contao/contao/pull/1982
[#2007]: https://github.com/contao/contao/pull/2007
[#2005]: https://github.com/contao/contao/pull/2005
[#1991]: https://github.com/contao/contao/pull/1991
[#1988]: https://github.com/contao/contao/pull/1988
[#1978]: https://github.com/contao/contao/pull/1978
[#1966]: https://github.com/contao/contao/pull/1966
[#1967]: https://github.com/contao/contao/pull/1967
[#1952]: https://github.com/contao/contao/pull/1952
[#1950]: https://github.com/contao/contao/pull/1950
[#1943]: https://github.com/contao/contao/pull/1943
[#1942]: https://github.com/contao/contao/pull/1942
[#1932]: https://github.com/contao/contao/pull/1932
[#1920]: https://github.com/contao/contao/pull/1920
[#1894]: https://github.com/contao/contao/pull/1894
[#1919]: https://github.com/contao/contao/pull/1919
[#1903]: https://github.com/contao/contao/pull/1903
[#1667]: https://github.com/contao/contao/pull/1667
[#1914]: https://github.com/contao/contao/pull/1914
[#1908]: https://github.com/contao/contao/pull/1908
[#1865]: https://github.com/contao/contao/pull/1865
[#1892]: https://github.com/contao/contao/pull/1892
[#1891]: https://github.com/contao/contao/pull/1891
[#1754]: https://github.com/contao/contao/pull/1754
[#1762]: https://github.com/contao/contao/pull/1762
[#1871]: https://github.com/contao/contao/pull/1871
[#1883]: https://github.com/contao/contao/pull/1883
[#1880]: https://github.com/contao/contao/pull/1880
[#1886]: https://github.com/contao/contao/pull/1886
[#1879]: https://github.com/contao/contao/pull/1879
[#1877]: https://github.com/contao/contao/pull/1877
[#1875]: https://github.com/contao/contao/pull/1875
[#1844]: https://github.com/contao/contao/pull/1844
[#1823]: https://github.com/contao/contao/pull/1823
[#1815]: https://github.com/contao/contao/pull/1815
[#1843]: https://github.com/contao/contao/pull/1843
[#1840]: https://github.com/contao/contao/pull/1840
[#1839]: https://github.com/contao/contao/pull/1839
[#1828]: https://github.com/contao/contao/pull/1828
[#1827]: https://github.com/contao/contao/pull/1827
[#1817]: https://github.com/contao/contao/pull/1817
[#1763]: https://github.com/contao/contao/pull/1763
[#1771]: https://github.com/contao/contao/pull/1771
[#1774]: https://github.com/contao/contao/pull/1774
[#1788]: https://github.com/contao/contao/pull/1788
[#1583]: https://github.com/contao/contao/pull/1583
[#1776]: https://github.com/contao/contao/pull/1776
[#1790]: https://github.com/contao/contao/pull/1790
[#1761]: https://github.com/contao/contao/pull/1761
[#1745]: https://github.com/contao/contao/pull/1745
[#1742]: https://github.com/contao/contao/pull/1742
[#1743]: https://github.com/contao/contao/pull/1743
[#1740]: https://github.com/contao/contao/pull/1740
[#1699]: https://github.com/contao/contao/pull/1699
[#1738]: https://github.com/contao/contao/pull/1738
[#1729]: https://github.com/contao/contao/pull/1729
[#1701]: https://github.com/contao/contao/pull/1701
[#1733]: https://github.com/contao/contao/pull/1733
[#1732]: https://github.com/contao/contao/pull/1732
[#1727]: https://github.com/contao/contao/pull/1727
[#1715]: https://github.com/contao/contao/pull/1715
[#1712]: https://github.com/contao/contao/pull/1712
[#1711]: https://github.com/contao/contao/pull/1711
[#1651]: https://github.com/contao/contao/pull/1651
[#1694]: https://github.com/contao/contao/pull/1694
[#1692]: https://github.com/contao/contao/pull/1692
[#1691]: https://github.com/contao/contao/pull/1691
[#1625]: https://github.com/contao/contao/pull/1625
[#1628]: https://github.com/contao/contao/pull/1628
[#1638]: https://github.com/contao/contao/pull/1638
[#1658]: https://github.com/contao/contao/pull/1658
[#1663]: https://github.com/contao/contao/pull/1663
[#1668]: https://github.com/contao/contao/pull/1668
[#1670]: https://github.com/contao/contao/pull/1670
[#1673]: https://github.com/contao/contao/pull/1673
[#1642]: https://github.com/contao/contao/pull/1642
[#1640]: https://github.com/contao/contao/pull/1640
[#1634]: https://github.com/contao/contao/pull/1634
[#1623]: https://github.com/contao/contao/pull/1623
[#1590]: https://github.com/contao/contao/pull/1590
[#1615]: https://github.com/contao/contao/pull/1615
[#1614]: https://github.com/contao/contao/pull/1614
[#1613]: https://github.com/contao/contao/pull/1613
[#1612]: https://github.com/contao/contao/pull/1612
[#1608]: https://github.com/contao/contao/pull/1608
[#1600]: https://github.com/contao/contao/pull/1600
[#1599]: https://github.com/contao/contao/pull/1599
[#1598]: https://github.com/contao/contao/pull/1598
[#1549]: https://github.com/contao/contao/pull/1549
[#1592]: https://github.com/contao/contao/pull/1592
[#1596]: https://github.com/contao/contao/pull/1596
[#1597]: https://github.com/contao/contao/pull/1597
[#1542]: https://github.com/contao/contao/pull/1542
[#1595]: https://github.com/contao/contao/pull/1595
[#1593]: https://github.com/contao/contao/pull/1593
[#1591]: https://github.com/contao/contao/pull/1591
[#1579]: https://github.com/contao/contao/pull/1579
[#1586]: https://github.com/contao/contao/pull/1586
[#1584]: https://github.com/contao/contao/pull/1584
[#1582]: https://github.com/contao/contao/pull/1582
[#1581]: https://github.com/contao/contao/pull/1581
[#1580]: https://github.com/contao/contao/pull/1580
[#1554]: https://github.com/contao/contao/pull/1554
[#1551]: https://github.com/contao/contao/pull/1551
[#1541]: https://github.com/contao/contao/pull/1541
[#1525]: https://github.com/contao/contao/pull/1525
[#1182]: https://github.com/contao/contao/pull/1182
[#1534]: https://github.com/contao/contao/pull/1534
[#1533]: https://github.com/contao/contao/pull/1533
[#1532]: https://github.com/contao/contao/pull/1532
[#1520]: https://github.com/contao/contao/pull/1520
[#1513]: https://github.com/contao/contao/pull/1513
[#1493]: https://github.com/contao/contao/pull/1493
[#1475]: https://github.com/contao/contao/pull/1475
[#1437]: https://github.com/contao/contao/pull/1437
[#1457]: https://github.com/contao/contao/pull/1457
[#1455]: https://github.com/contao/contao/pull/1455
[#1450]: https://github.com/contao/contao/pull/1450
[#1439]: https://github.com/contao/contao/pull/1439
[#1444]: https://github.com/contao/contao/pull/1444
[#1445]: https://github.com/contao/contao/pull/1445
[#1453]: https://github.com/contao/contao/pull/1453
[#1423]: https://github.com/contao/contao/pull/1423
[#1420]: https://github.com/contao/contao/pull/1420
[#1421]: https://github.com/contao/contao/pull/1421
[#1411]: https://github.com/contao/contao/pull/1411
[#1419]: https://github.com/contao/contao/pull/1419
[#1416]: https://github.com/contao/contao/pull/1416
[#1417]: https://github.com/contao/contao/pull/1417
[#1418]: https://github.com/contao/contao/pull/1418
[#1413]: https://github.com/contao/contao/pull/1413
[#1396]: https://github.com/contao/contao/pull/1396
[#1385]: https://github.com/contao/contao/pull/1385
[#1369]: https://github.com/contao/contao/pull/1369
[#1370]: https://github.com/contao/contao/pull/1370
[#1377]: https://github.com/contao/contao/pull/1377
[#1376]: https://github.com/contao/contao/pull/1376
[#1375]: https://github.com/contao/contao/pull/1375
[#1374]: https://github.com/contao/contao/pull/1374
[#1373]: https://github.com/contao/contao/pull/1373
[#1372]: https://github.com/contao/contao/pull/1372
[#1359]: https://github.com/contao/contao/pull/1359
[#1364]: https://github.com/contao/contao/pull/1364
[#1354]: https://github.com/contao/contao/pull/1354
[#1348]: https://github.com/contao/contao/pull/1348
[#1336]: https://github.com/contao/contao/pull/1336
[#1250]: https://github.com/contao/contao/pull/1250
[#1335]: https://github.com/contao/contao/pull/1335
[#1323]: https://github.com/contao/contao/pull/1323
[#1299]: https://github.com/contao/contao/pull/1299
[#1327]: https://github.com/contao/contao/pull/1327
[#1324]: https://github.com/contao/contao/pull/1324
[#1322]: https://github.com/contao/contao/pull/1322
[#1320]: https://github.com/contao/contao/pull/1320
[#1295]: https://github.com/contao/contao/pull/1295
[#1292]: https://github.com/contao/contao/pull/1292
[#1293]: https://github.com/contao/contao/pull/1293
[#1291]: https://github.com/contao/contao/pull/1291
[#1286]: https://github.com/contao/contao/pull/1286
[#1267]: https://github.com/contao/contao/pull/1267
[#1275]: https://github.com/contao/contao/pull/1275
[#1257]: https://github.com/contao/contao/pull/1257
[#1289]: https://github.com/contao/contao/pull/1289
[#1282]: https://github.com/contao/contao/pull/1282
[#1283]: https://github.com/contao/contao/pull/1283
[#1284]: https://github.com/contao/contao/pull/1284
[#1230]: https://github.com/contao/contao/pull/1230
[#1255]: https://github.com/contao/contao/pull/1255
[#1222]: https://github.com/contao/contao/pull/1222
[#1246]: https://github.com/contao/contao/pull/1246
[#1237]: https://github.com/contao/contao/pull/1237
[#1217]: https://github.com/contao/contao/pull/1217
[#1232]: https://github.com/contao/contao/pull/1232
[#1225]: https://github.com/contao/contao/pull/1225
[#1223]: https://github.com/contao/contao/pull/1223
[#1221]: https://github.com/contao/contao/pull/1221
[#559]: https://github.com/contao/contao/pull/559
[#1165]: https://github.com/contao/contao/pull/1165
[#1184]: https://github.com/contao/contao/pull/1184
[#1098]: https://github.com/contao/contao/pull/1098
[#1180]: https://github.com/contao/contao/pull/1180
[#1178]: https://github.com/contao/contao/pull/1178
[#1057]: https://github.com/contao/contao/pull/1057
[#580]: https://github.com/contao/contao/pull/580
[#579]: https://github.com/contao/contao/pull/579
[#521]: https://github.com/contao/contao/pull/521
[#1154]: https://github.com/contao/contao/pull/1154
[#989]: https://github.com/contao/contao/pull/989
[#709]: https://github.com/contao/contao/pull/709
[#581]: https://github.com/contao/contao/pull/581
[#860]: https://github.com/contao/contao/pull/860
[#1068]: https://github.com/contao/contao/pull/1068
[#1132]: https://github.com/contao/contao/pull/1132
[#705]: https://github.com/contao/contao/pull/705
[#1121]: https://github.com/contao/contao/pull/1121
[#1125]: https://github.com/contao/contao/pull/1125
[#1129]: https://github.com/contao/contao/pull/1129
[#1122]: https://github.com/contao/contao/pull/1122
[#1123]: https://github.com/contao/contao/pull/1123
[#1124]: https://github.com/contao/contao/pull/1124
[#1115]: https://github.com/contao/contao/pull/1115
[#1101]: https://github.com/contao/contao/pull/1101
[#714]: https://github.com/contao/contao/pull/714
[#1085]: https://github.com/contao/contao/pull/1085
[#1078]: https://github.com/contao/contao/pull/1078
[#1094]: https://github.com/contao/contao/pull/1094
[#1080]: https://github.com/contao/contao/pull/1080
[#1086]: https://github.com/contao/contao/pull/1086
[#718]: https://github.com/contao/contao/pull/718
[#1063]: https://github.com/contao/contao/pull/1063
[#603]: https://github.com/contao/contao/pull/603
[#1055]: https://github.com/contao/contao/pull/1055
[#1045]: https://github.com/contao/contao/pull/1045
[#1053]: https://github.com/contao/contao/pull/1053
[#1058]: https://github.com/contao/contao/pull/1058
[#1056]: https://github.com/contao/contao/pull/1056
[#1052]: https://github.com/contao/contao/pull/1052
[#954]: https://github.com/contao/contao/pull/954
[#985]: https://github.com/contao/contao/pull/985
[#852]: https://github.com/contao/contao/pull/852
[#1034]: https://github.com/contao/contao/pull/1034
[#1039]: https://github.com/contao/contao/pull/1039
[#1027]: https://github.com/contao/contao/pull/1027
[#983]: https://github.com/contao/contao/pull/983
[#621]: https://github.com/contao/contao/pull/621
[#1012]: https://github.com/contao/contao/pull/1012
[#990]: https://github.com/contao/contao/pull/990
[#976]: https://github.com/contao/contao/pull/976
[#968]: https://github.com/contao/contao/pull/968
[#840]: https://github.com/contao/contao/pull/840
[#955]: https://github.com/contao/contao/pull/955
[#639]: https://github.com/contao/contao/pull/639
[#946]: https://github.com/contao/contao/pull/946
[#948]: https://github.com/contao/contao/pull/948
[#887]: https://github.com/contao/contao/pull/887
[#945]: https://github.com/contao/contao/pull/945
[#943]: https://github.com/contao/contao/pull/943
[#810]: https://github.com/contao/contao/pull/810
[#730]: https://github.com/contao/contao/pull/730
[#604]: https://github.com/contao/contao/pull/604
[#672]: https://github.com/contao/contao/pull/672
[#768]: https://github.com/contao/contao/pull/768
[#762]: https://github.com/contao/contao/pull/762
[#776]: https://github.com/contao/contao/pull/776
[#717]: https://github.com/contao/contao/pull/717
[#703]: https://github.com/contao/contao/pull/703
[#1212]: https://github.com/contao/contao/pull/1212
[#1213]: https://github.com/contao/contao/pull/1213
[#1210]: https://github.com/contao/contao/pull/1210
[#1211]: https://github.com/contao/contao/pull/1211
[#1209]: https://github.com/contao/contao/pull/1209
[#806]: https://github.com/contao/contao/pull/806
[#1199]: https://github.com/contao/contao/pull/1199
[#1201]: https://github.com/contao/contao/pull/1201
[#1203]: https://github.com/contao/contao/pull/1203
[#1177]: https://github.com/contao/contao/pull/1177
[#1196]: https://github.com/contao/contao/pull/1196
[#1197]: https://github.com/contao/contao/pull/1197
[#1202]: https://github.com/contao/contao/pull/1202
[#1195]: https://github.com/contao/contao/pull/1195
[#1189]: https://github.com/contao/contao/pull/1189
[#1190]: https://github.com/contao/contao/pull/1190
[#1191]: https://github.com/contao/contao/pull/1191
[#1194]: https://github.com/contao/contao/pull/1194
[#1188]: https://github.com/contao/contao/pull/1188
[#1181]: https://github.com/contao/contao/pull/1181
[#1173]: https://github.com/contao/contao/pull/1173
[#1179]: https://github.com/contao/contao/pull/1179
[#1175]: https://github.com/contao/contao/pull/1175
[#1176]: https://github.com/contao/contao/pull/1176
[#1048]: https://github.com/contao/contao/pull/1048
[#1171]: https://github.com/contao/contao/pull/1171
[#1118]: https://github.com/contao/contao/pull/1118
[#1169]: https://github.com/contao/contao/pull/1169
[#1164]: https://github.com/contao/contao/pull/1164
[#1162]: https://github.com/contao/contao/pull/1162
[#1159]: https://github.com/contao/contao/pull/1159
[#1158]: https://github.com/contao/contao/pull/1158
[#1155]: https://github.com/contao/contao/pull/1155
[#1157]: https://github.com/contao/contao/pull/1157
[#1153]: https://github.com/contao/contao/pull/1153
[#1151]: https://github.com/contao/contao/pull/1151
[#1149]: https://github.com/contao/contao/pull/1149
[#1150]: https://github.com/contao/contao/pull/1150
[#1148]: https://github.com/contao/contao/pull/1148
[#1147]: https://github.com/contao/contao/pull/1147
[#1131]: https://github.com/contao/contao/pull/1131
[#1128]: https://github.com/contao/contao/pull/1128
[#1126]: https://github.com/contao/contao/pull/1126
[#1092]: https://github.com/contao/contao/pull/1092
[#1120]: https://github.com/contao/contao/pull/1120
[#1116]: https://github.com/contao/contao/pull/1116
[#1113]: https://github.com/contao/contao/pull/1113
[#1114]: https://github.com/contao/contao/pull/1114
[#1100]: https://github.com/contao/contao/pull/1100
[#1102]: https://github.com/contao/contao/pull/1102
[#1095]: https://github.com/contao/contao/pull/1095
[#1097]: https://github.com/contao/contao/pull/1097
[#1054]: https://github.com/contao/contao/pull/1054
[#1046]: https://github.com/contao/contao/pull/1046
[#1050]: https://github.com/contao/contao/pull/1050
[#1033]: https://github.com/contao/contao/pull/1033
[#1013]: https://github.com/contao/contao/pull/1013
[#1010]: https://github.com/contao/contao/pull/1010
[#991]: https://github.com/contao/contao/pull/991
