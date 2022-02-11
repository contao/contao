# Changelog

This project adheres to [Semantic Versioning].

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
[4.13.0-RC2]: https://github.com/contao/contao/releases/tag/4.13.0-RC2
[4.13.0-RC1]: https://github.com/contao/contao/releases/tag/4.13.0-RC1
[aschempp]: https://github.com/aschempp
[ausi]: https://github.com/ausi
[bezin]: https://github.com/bezin
[bytehead]: https://github.com/bytehead
[dennisbohn]: https://github.com/dennisbohn
[doishub]: https://github.com/doishub
[fritzmg]: https://github.com/fritzmg
[leofeyer]: https://github.com/leofeyer
[m-vo]: https://github.com/m-vo
[MarkejN]: https://github.com/MarkejN
[qzminski]: https://github.com/qzminski
[rabauss]: https://github.com/rabauss
[richardhj]: https://github.com/richardhj
[SeverinGloeckle]: https://github.com/SeverinGloeckle
[sheeep]: https://github.com/sheeep
[Toflar]: https://github.com/Toflar
[xprojects-de]: https://github.com/xprojects-de
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
