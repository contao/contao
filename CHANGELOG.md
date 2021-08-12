# Changelog

This project adheres to [Semantic Versioning].

## [4.11.8] (2021-08-12)

## [4.11.7] (2021-08-11)

**Security fixes:**

- Prevent privilege escalation with the form generator ([CVE-2021-37627])
- Prevent PHP file inclusion via insert tags ([CVE-2021-37626])
- Prevent XSS via HTML attributes in the back end ([CVE-2021-35955])

## [4.11.6] (2021-08-04)

**Fixed issues:**

- [#3253] Fix more invalid array access and missing null checks ([m-vo])
- [#3208] Always concatenate the 'image_container' class in figure.html.twig ([m-vo])
- [#3130] Add more null checks for DCA lang references ([m-vo])
- [#3123] Fix another invalid array access in ModuleProxy ([m-vo])
- [#3090] Allow empty content element translation key ([leofeyer])
- [#3098] Automatically inject container for tagged controllers ([aschempp])
- [#3087] Remove two superfluous labels ([leofeyer])
- [#3079] Fix another invalid array access ([m-vo])
- [#2931] Fix filtering for recurring events ([fritzmg])

## [4.11.5] (2021-06-23)

**Security fixes:**

- Prevent XSS in the system log ([CVE-2021-35210])

**Fixed issues:**

- [#3113] Use "anon." as username if authentication fails ([leofeyer])

## [4.11.4] (2021-06-09)

**Fixed issues:**

- [#3048] Fix another PHP 8 "undefined array key" issue ([leofeyer])
- [#2987] Add tests for the image studio macros ([m-vo])
- [#2937] Fix PHP 8 compat of DC_Table/Environment ([rabauss])
- [#3004] Fix an inconsistency in the routing functional tests ([Toflar])

## [4.11.3] (2021-05-13)

**Fixed issues:**

- [#2991] Enable "useSSL" by default unless the backend request is insecure ([ausi])
- [#2969] Fix more PHP 8 undefined array index errors ([ausi])
- [#2982] Add width/height attributes to the picture source ([ausi])
- [#2966] Update the functional tests section in README.md ([ArndtZiegler])
- [#2927] Use CSS to add the main headline separators ([leofeyer])
- [#2919] Prevent an 'Undefined array key "id"' warning in the clipboard ([leofeyer])
- [#2923] Fix more PHP 8 undefined array index errors ([ausi])
- [#2922] Fix another PHP 8 undefined array index error ([ausi])

## [4.11.2] (2021-03-25)

**Fixed issues:**

- [#2915] Fix the version 4.8.0 update ([leofeyer])
- [#2911] Fix more PHP 8 warnings ([leofeyer])
- [#2908] Add a command to debug the page controllers ([aschempp])
- [#2907] Manually override content composition for known legacy types ([aschempp])
- [#2902] Fix the list/explodes when the second variable can be null ([leofeyer])
- [#2858] Quote the "group" field in the UserCreateCommand statement ([richardhj])
- [#2706] Add support for namespaced DC drivers ([Toflar])
- [#2845] Always show all errors in the contao-setup binary ([m-vo])
- [#2843] Fix another illegal array access in System::getReferer() ([m-vo])
- [#2856] Fix the search query if there are no keywords ([ausi])

## [4.11.1] (2021-03-04)

**Fixed issues:**

- [#2785] Handle null arguments in the ContentCompositionListener ([fritzmg])
- [#2835] Fix an illegal object access in the Versions class ([leofeyer])
- [#2833] Use dependency injection for the InitializeController ([aschempp])
- [#2834] Allow passing an array of IDs to User::isMemberOf() ([leofeyer])
- [#2805] Fix an illegal array access in DC_Table when expanding the tree ([m-vo])
- [#2818] Fix the logout handler in Symfony 5 ([fritzmg])
- [#2794] Handle another illegal array access in the tl_page DCA ([m-vo])
- [#2788] Fix accessing Model\Collection instead of Model in ModuleFaqPage ([m-vo])
- [#2784] Correctly sort the pages if the URL suffix is empty ([aschempp])
- [#2806] Fix accessing an undefined variable ([m-vo])
- [#2796] Suggest using the contao-setup binary with @php prefix ([m-vo])
- [#2783] Correctly merge image size _defaults ([m-vo])
- [#2782] Fix the type casting for the FigureBuilder::enableLightbox() method ([richardhj])
- [#2774] Do not use Kernel::$rootDir anymore ([fritzmg])

## [4.11.0] (2021-02-17)

**Fixed issues:**

- [#2763] Fix an illegal array access in BackendUser::navigation() ([m-vo])
- [#2764] Fix an illegal array access in DC_Table::reviseTables() ([m-vo])
- [#2766] Automatically prefix the back end attributes ([leofeyer])
- [#2752] Symlink highlight.php as highlight_php ([leofeyer])
- [#2743] Change the default URL suffix ([leofeyer])
- [#2732] Handle non-existing resources in the FigureRenderer ([m-vo])
- [#2731] Do not replace template data recursively when applying legacy template data ([m-vo])
- [#2704] Fix the rgxp=>httpurl implementation ([leofeyer])

## [4.11.0-RC2] (2021-01-29)

**Fixed issues:**

- [#2703] Correctly show fields with an input_field_callback ([leofeyer])
- [#2702] Register the SitemapController in the services.yml ([leofeyer])
- [#2701] Correctly match page controllers with absolute paths ([aschempp])
- [#2698] Handle root pages without hostname in the SitemapController ([Toflar])
- [#2694] Remove the legacy encryption logic ([Toflar])
- [#2693] Fix a wrong class reference in the Widget class ([leofeyer])
- [#2679] Fix yet another E_WARNING issue ([leofeyer])
- [#2662] Fix entity encoding in the figure insert tag ([m-vo])
- [#2661] Use type="url" for httpurl text fields ([fritzmg])

## [4.11.0-RC1] (2021-01-18)

**Security fixes:**

- Prevent insert tag injection in forms ([CVE-2020-25768])

**New features:**

- [#2607] Add a "figure" insert tag ([m-vo])
- [#2297] Add a "httpurl" and a "custom" rgxp option ([fritzmg])
- [#2183] Use a binary instead of a Composer script handler ([m-vo])
- [#2501] Handle altering Twig bundle paths at runtime ([m-vo])
- [#2187] Support insert tags in Twig templates ([m-vo])
- [#1999] Add configuration options for the back end theme ([rabauss])
- [#2049] Drop the schema filter and adjust the schema generator ([m-vo])
- [#2293] Store the 2FA backup codes hashed ([bytehead])
- [#2072] Deprecate the Controller::addImageToTemplate() method ([m-vo])
- [#2232] Auto-configure doctrine.orm.mappings for app entities ([m-vo])
- [#1937] Allow rendering a figure inline from PHP templates ([m-vo])
- [#2438] Remove the CDN integration of MooTools and jQuery ([Toflar])
- [#2554] Support native transport and other mailer transport options ([fritzmg])
- [#1779] Allow overriding page title and description in FAQs ([dmolineus])
- [#1301] Add a "copy URL" button in the preview toolbar ([simonreitinger])
- [#2600] Remove the Composer script handler ([leofeyer])
- [#2593] Replace highlight.js with highlight.php ([leofeyer])
- [#1941] Make Contao compatible with Symfony 5.2 ([leofeyer])
- [#2568] Restore compatibility with E_WARNING ([leofeyer])
- [#2295] Dynamically fetch the sitemap XML files ([Toflar])
- [#2432] Use meaningful values for the "autocomplete" attributes ([leofeyer])
- [#2431] Remove the Google web fonts field from the page layout ([leofeyer])
- [#2421] Stop using the deprecated Doctrine methods ([leofeyer])
- [#2051] Allow editing Twig files in the template editor ([m-vo])
- [#2368] Add a createIfDeferred() method to the ImageResult class ([m-vo])
- [#2243] Allow defining default values for the contao.image.sizes config ([m-vo])
- [#2404] Remove the "add language" menu from the meta wizard ([leofeyer])

**Fixed issues:**

- [#2606] Fix another E_WARNING issue ([leofeyer])
- [#2605] Symlink the highlight.php styles folder ([leofeyer])
- [#2604] Update the CONTRIBUTORS.md ([leofeyer])
- [#2599] Fix more E_WARNING issues ([leofeyer])
- [#2595] Fix the OrderFieldMigration class ([leofeyer])
- [#2594] Fix two more E_WARNING issues ([leofeyer])
- [#2510] Add compatibility with terminal42/escargot version 1.0 ([ausi])
- [#2284] Replace phpunit/token-stream with nikic/php-parser ([m-vo])

[Semantic Versioning]: https://semver.org/spec/v2.0.0.html
[4.11.8]: https://github.com/contao/contao/releases/tag/4.11.8
[4.11.7]: https://github.com/contao/contao/releases/tag/4.11.7
[4.11.6]: https://github.com/contao/contao/releases/tag/4.11.6
[4.11.5]: https://github.com/contao/contao/releases/tag/4.11.5
[4.11.4]: https://github.com/contao/contao/releases/tag/4.11.4
[4.11.3]: https://github.com/contao/contao/releases/tag/4.11.3
[4.11.2]: https://github.com/contao/contao/releases/tag/4.11.2
[4.11.1]: https://github.com/contao/contao/releases/tag/4.11.1
[4.11.0]: https://github.com/contao/contao/releases/tag/4.11.0
[4.11.0-RC2]: https://github.com/contao/contao/releases/tag/4.11.0-RC2
[4.11.0-RC1]: https://github.com/contao/contao/releases/tag/4.11.0-RC1
[CVE-2021-37627]: https://github.com/contao/contao/security/advisories/GHSA-hq5m-mqmx-fw6m
[CVE-2021-37626]: https://github.com/contao/contao/security/advisories/GHSA-r6mv-ppjc-4hgr
[CVE-2021-35955]: https://github.com/contao/contao/security/advisories/GHSA-hr3h-x6gq-rqcp
[CVE-2021-35210]: https://github.com/contao/contao/security/advisories/GHSA-h58v-c6rf-g9f7
[CVE-2020-25768]: https://github.com/contao/contao/security/advisories/GHSA-f7wm-x4gw-6m23
[ArndtZiegler]: https://github.com/ArndtZiegler
[aschempp]: https://github.com/aschempp
[ausi]: https://github.com/ausi
[bytehead]: https://github.com/bytehead
[dmolineus]: https://github.com/dmolineus
[fritzmg]: https://github.com/fritzmg
[leofeyer]: https://github.com/leofeyer
[m-vo]: https://github.com/m-vo
[rabauss]: https://github.com/rabauss
[richardhj]: https://github.com/richardhj
[simonreitinger]: https://github.com/simonreitinger
[Toflar]: https://github.com/Toflar
[#3253]: https://github.com/contao/contao/pull/3253
[#3208]: https://github.com/contao/contao/pull/3208
[#3130]: https://github.com/contao/contao/pull/3130
[#3123]: https://github.com/contao/contao/pull/3123
[#3090]: https://github.com/contao/contao/pull/3090
[#3098]: https://github.com/contao/contao/pull/3098
[#3087]: https://github.com/contao/contao/pull/3087
[#3079]: https://github.com/contao/contao/pull/3079
[#2931]: https://github.com/contao/contao/pull/2931
[#3113]: https://github.com/contao/contao/pull/3113
[#3048]: https://github.com/contao/contao/pull/3048
[#2987]: https://github.com/contao/contao/pull/2987
[#2937]: https://github.com/contao/contao/pull/2937
[#3004]: https://github.com/contao/contao/pull/3004
[#2991]: https://github.com/contao/contao/pull/2991
[#2969]: https://github.com/contao/contao/pull/2969
[#2982]: https://github.com/contao/contao/pull/2982
[#2966]: https://github.com/contao/contao/pull/2966
[#2927]: https://github.com/contao/contao/pull/2927
[#2919]: https://github.com/contao/contao/pull/2919
[#2923]: https://github.com/contao/contao/pull/2923
[#2922]: https://github.com/contao/contao/pull/2922
[#2915]: https://github.com/contao/contao/pull/2915
[#2911]: https://github.com/contao/contao/pull/2911
[#2908]: https://github.com/contao/contao/pull/2908
[#2907]: https://github.com/contao/contao/pull/2907
[#2902]: https://github.com/contao/contao/pull/2902
[#2858]: https://github.com/contao/contao/pull/2858
[#2706]: https://github.com/contao/contao/pull/2706
[#2845]: https://github.com/contao/contao/pull/2845
[#2843]: https://github.com/contao/contao/pull/2843
[#2856]: https://github.com/contao/contao/pull/2856
[#2785]: https://github.com/contao/contao/pull/2785
[#2835]: https://github.com/contao/contao/pull/2835
[#2833]: https://github.com/contao/contao/pull/2833
[#2834]: https://github.com/contao/contao/pull/2834
[#2805]: https://github.com/contao/contao/pull/2805
[#2818]: https://github.com/contao/contao/pull/2818
[#2794]: https://github.com/contao/contao/pull/2794
[#2788]: https://github.com/contao/contao/pull/2788
[#2784]: https://github.com/contao/contao/pull/2784
[#2806]: https://github.com/contao/contao/pull/2806
[#2796]: https://github.com/contao/contao/pull/2796
[#2783]: https://github.com/contao/contao/pull/2783
[#2782]: https://github.com/contao/contao/pull/2782
[#2774]: https://github.com/contao/contao/pull/2774
[#2763]: https://github.com/contao/contao/pull/2763
[#2764]: https://github.com/contao/contao/pull/2764
[#2766]: https://github.com/contao/contao/pull/2766
[#2752]: https://github.com/contao/contao/pull/2752
[#2743]: https://github.com/contao/contao/pull/2743
[#2732]: https://github.com/contao/contao/pull/2732
[#2731]: https://github.com/contao/contao/pull/2731
[#2704]: https://github.com/contao/contao/pull/2704
[#2703]: https://github.com/contao/contao/pull/2703
[#2702]: https://github.com/contao/contao/pull/2702
[#2701]: https://github.com/contao/contao/pull/2701
[#2698]: https://github.com/contao/contao/pull/2698
[#2694]: https://github.com/contao/contao/pull/2694
[#2693]: https://github.com/contao/contao/pull/2693
[#2679]: https://github.com/contao/contao/pull/2679
[#2662]: https://github.com/contao/contao/pull/2662
[#2661]: https://github.com/contao/contao/pull/2661
[#2607]: https://github.com/contao/contao/pull/2607
[#2297]: https://github.com/contao/contao/pull/2297
[#2183]: https://github.com/contao/contao/pull/2183
[#2501]: https://github.com/contao/contao/pull/2501
[#2187]: https://github.com/contao/contao/pull/2187
[#1999]: https://github.com/contao/contao/pull/1999
[#2049]: https://github.com/contao/contao/pull/2049
[#2293]: https://github.com/contao/contao/pull/2293
[#2072]: https://github.com/contao/contao/pull/2072
[#2232]: https://github.com/contao/contao/pull/2232
[#1937]: https://github.com/contao/contao/pull/1937
[#2438]: https://github.com/contao/contao/pull/2438
[#2554]: https://github.com/contao/contao/pull/2554
[#1779]: https://github.com/contao/contao/pull/1779
[#1301]: https://github.com/contao/contao/pull/1301
[#2600]: https://github.com/contao/contao/pull/2600
[#2593]: https://github.com/contao/contao/pull/2593
[#1941]: https://github.com/contao/contao/pull/1941
[#2568]: https://github.com/contao/contao/pull/2568
[#2295]: https://github.com/contao/contao/pull/2295
[#2432]: https://github.com/contao/contao/pull/2432
[#2431]: https://github.com/contao/contao/pull/2431
[#2421]: https://github.com/contao/contao/pull/2421
[#2051]: https://github.com/contao/contao/pull/2051
[#2368]: https://github.com/contao/contao/pull/2368
[#2243]: https://github.com/contao/contao/pull/2243
[#2404]: https://github.com/contao/contao/pull/2404
[#2606]: https://github.com/contao/contao/pull/2606
[#2605]: https://github.com/contao/contao/pull/2605
[#2604]: https://github.com/contao/contao/pull/2604
[#2599]: https://github.com/contao/contao/pull/2599
[#2595]: https://github.com/contao/contao/pull/2595
[#2594]: https://github.com/contao/contao/pull/2594
[#2510]: https://github.com/contao/contao/pull/2510
[#2284]: https://github.com/contao/contao/pull/2284
