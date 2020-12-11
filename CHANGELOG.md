# Changelog

This project adheres to [Semantic Versioning].

## [4.10.5] (2020-12-11)

**Fixed issues:**

- [#2553] Strip port numbers from root page domains ([leofeyer])
- [#2532] Trigger the onload_callback when featuring news/events ([leofeyer])
- [#2524] Add the feature action to the permission checks ([bytehead])
- [#2314] Add missing ResizeOptions to the studio classes ([m-vo])

## [4.10.4] (2020-10-20)

**Fixed issues:**

- [#2419] Fix unknown option when creating or editing error pages ([fritzmg])
- [#2423] Correctly show the xlabel in the checkbox wizard ([leofeyer])

## [4.10.3] (2020-10-07)

**New features:**

- [#2379] Add the image_container class to the default figure template ([m-vo])

**Fixed issues:**

- [#2410] Port the numeric alias changes to the PageUrlListener class ([leofeyer])
- [#2341] Make the SimpleTokenParser backwards compatible ([m-vo])
- [#2397] Check allowed page types ([aschempp])
- [#2337] Fix the asset URL handling in the image studio ([m-vo])
- [#2265] Purge the search tables directly ([aschempp])
- [#2372] Remove existing tags from annotations that could  be arrays ([aschempp])
- [#2385] Check submitInput() again after validation ([leofeyer])
- [#2365] Fix non-normalized file extension comparisons when building lightboxes ([m-vo])

## [4.10.2] (2020-09-25)

## [4.10.1] (2020-09-24)

**Fixed issues:**

- [#2328] Fix the popup button padding ([leofeyer])
- [#2323] Fix the routing SSL logic ([leofeyer])
- [#2311] Fix the FigureTest paths ([m-vo])
- [#2298] Do not predefine the number of Psalm threads ([leofeyer])
- [#2280] Update the UPGRADE.md file ([leofeyer])
- [#2270] Fix a wrong type hint ([bytehead])
- [#2267] Keep the fileTree sorting order when changing selection ([ausi])
- [#2261] Do not validate widgets that do not submit input ([leofeyer])
- [#2250] Skip indexing if a page has not changed ([ausi])
- [#2227] Use a temporary status code to redirect to the language root ([leofeyer])
- [#2247] Rename the SimpleTokenParser::parseTokens() method ([leofeyer])
- [#2173] Work towards Psalm level 6 (part 2) ([m-vo])
- [#2234] Review the composite indexes ([leofeyer])
- [#2226] Do not add the first/last classes to articles ([leofeyer])
- [#2184] Fix invalid "array to string conversion" in ScriptHandler exception message ([m-vo])
- [#2172] Prevent zero values for idf measure in search algorithm ([ausi])
- [#2167] Also add a template block for "content_css" ([frontendschlampe])

## [4.10.0] (2020-08-18)

## [4.10.0-RC4] (2020-08-14)

**Fixed issues:**

- [#2151] Correctly inherit the disableLanguageRedirect property ([aschempp])
- [#2145] Disable the no-language-redirect feature in legacy routing mode ([aschempp])
- [#2144] Correctly handle multiple slashes in a path ([aschempp])
- [#2136] Decorate mailer.mailer instead of mailer ([bytehead])

## [4.10.0-RC3] (2020-08-06)

**New features:**

- [#2017] Allow to not redirect to a language ([aschempp])
- [#2021] Upgrade to TinyMCE 5 ([leofeyer])

**Fixed issues:**

- [#2019] Add custom exception to further handle route parameter errors ([aschempp])
- [#2071] Also add template options to legacy template data ([m-vo])
- [#2098] Use HTTP status code 303 instead of 307 for redirects ([leofeyer])
- [#2065] Enable legacy routing by default ([aschempp])
- [#2064] Fix and improve route debugging ([aschempp])
- [#2069] Correctly set the active record in the ContentCompositionListener ([leofeyer])
- [#2068] Improve the PageUrlListener in "edit multiple" mode ([leofeyer])
- [#2060] Support getOverwriteMetadata() in more models ([m-vo])
- [#2062] Correctly deprecate the ampersand() function ([leofeyer])
- [#2061] Fix a case-sensitivity mismatch in the figure builder ([leofeyer])
- [#2059] Remove content routing ([aschempp])
- [#2048] Re-arrange the root page form layout ([leofeyer])
- [#2041] Fix a memory leak in the resize images command ([leofeyer])
- [#2042] Add missing where condition for url prefix check ([ausi])
- [#1984] Fix the SERP preview with mandatory route parameters ([bytehead])
- [#2026] Use escapeshellarg() in the symfony/process BC layer ([leofeyer])
- [#2024] Fix the .tox-menubar format definition ([leofeyer])
- [#2018] Ignore pages without alias when checking for duplicates ([aschempp])
- [#1985] Update terminal42/service-annotation-bundle and fix Page annotation ([aschempp])
- [#2008] Prevent installing jQuery <3.5 ([leofeyer])
- [#2009] Upgrade to highlight.js 10 ([leofeyer])
- [#2000] Fix the search index subscriber test ([leofeyer])
- [#1986] Private properties in ImageResult and LightboxResult ([m-vo])
- [#1987] Also migrate the "useFolderUrl" setting ([fritzmg])
- [#1990] Support indexed tokens in the SimpleTokenParser ([fritzmg])

## [4.10.0-RC2] (2020-07-24)

**Fixed issues:**

- [#1972] Fix the front end preview ([leofeyer])
- [#1975] Always include the default value in the generated URL ([bytehead])
- [#1973] Rewrite the page annotation to follow the Route annotation ([aschempp])
- [#1976] Improve the search result highlighting performance ([ausi])
- [#1964] Fix the Composer script handlers ([leofeyer])
- [#1970] Prevent type errors in the button callback functions ([leofeyer])
- [#1971] Add a missing type-hint in the PageRoute constructor ([leofeyer])
- [#1969] Fix the order field migration ([ausi])
- [#1961] Convert non existing $_POST fields to an empty string ([leofeyer])
- [#1960] Fix two MySQL 8 issues ([leofeyer])

## [4.10.0-RC1] (2020-07-21)

**New features:**

- [#1516] Enhance the routing implementation ([aschempp])
- [#1528] Add a Twig helper for rendering figures ([m-vo])
- [#1830] Make configured mailer transports selectable in the back end ([fritzmg])
- [#1463] Add the contao:user:create and contao:user:list commands ([richardhj])
- [#1753] Add the image studio ([m-vo])
- [#1768] Add the "format_date" and "convert_date" insert tags ([fritzmg])
- [#1756] Add a simple token parser service ([m-vo])
- [#1864] Support all backend user permissions in the back end access voter ([aschempp])
- [#1902] Use webmozart/path-util for all path operations ([m-vo])
- [#1904] Remove the ID column from the search index table ([ausi])
- [#1893] Allow new major versions of third-party packages ([leofeyer])
- [#1679] Make the search storage more efficient ([ausi])
- [#1890] Allow more recent Doctrine versions in the composer.json ([leofeyer])
- [#1896] Allow ContainerBuilder with different configurations ([aschempp])
- [#1798] Do not use blacklist and whitelist ([Toflar])
- [#1829] Replace symfony/swiftmailer-bundle with symfony/mailer ([fritzmg])
- [#1850] Optimize importing DB fixtures in the functional tests ([leofeyer])
- [#1382] Disable settings in the back end if defined in the container ([dmolineus])
- [#1881] Allow to define a default ptable ([leofeyer])
- [#1446] Determine ptable dynamically ([fritzmg])
- [#1778] Allow overriding robots settings in news and events ([dmolineus])
- [#1870] Add csv_excel format to form generator ([ausi])
- [#1869] Do not use the contao_root route ([aschempp])
- [#1465] Add featured events ([bytehead])
- [#1826] Improve the StripCookiesSubscriber::filterCookies() method ([ausi])
- [#1652] Let the pretty error screen handle unused argument exceptions ([aschempp])
- [#1650] Fallback to the regular icon if a page type has no icon ([aschempp])
- [#1509] Support Symfony Expression Language in Simple Tokens ([Toflar])
- [#1624] Allow stripping query parameters in the reverse proxy ([Toflar])
- [#1518] Split routing functional tests into separate YAML files ([aschempp])
- [#1526] Add ID to filter box for tl_module ([eS-IT])
- [#1506] Convert functional test SQL to YAML ([aschempp])
- [#1501] Rename routing.yml to routes.yml ([aschempp])
- [#1468] Use "isSortable" in file and page trees without orderField ([ausi])
- [#1483] Add Psalm with level 7 ([bytehead])
- [#1480] Deprecate the remaining helper functions ([ausi])
- [#1447] Re-add the folderUrl setting to the root page ([aschempp])

**Fixed issues:**

- [#1951] Explicitly register the model classes ([leofeyer])
- [#1948] Greatly simplify the PhpStan configuration ([leofeyer])
- [#1946] Use symfony/deprecation-contracts to trigger deprecation warnings ([leofeyer])
- [#1954] Clean up the routing changes ([leofeyer])
- [#1953] Rename "light box" to "lightbox" ([m-vo])
- [#1944] Fix the lightbox group identifier description ([m-vo])
- [#1924] Unlock phpstan level 5 ([m-vo])
- [#1936] Remove @param Something&MockObject annotations ([leofeyer])
- [#1931] Fix a merge error ([ausi])
- [#1930] Replace lexik/maintenance-bundle with our Symfony 5 compatible fork ([leofeyer])
- [#1916] Replace the deprecated Doctrine cache bundle ([leofeyer])
- [#1918] Restore the old StringUtil logic (but compare on normalized strings) ([m-vo])
- [#1915] Fix two left-over path util usages ([leofeyer])
- [#1911] Remove an incorrect BC layer ([Toflar])
- [#1900] Use createResult() in the OrderFieldMigration ([fritzmg])
- [#1895] Support projectDir instead of rootDir for ContaoModuleBundle ([aschempp])
- [#1884] Get rid of more deprecations ([leofeyer])
- [#1872] Make the functional tests compatible with Symfony 5 ([leofeyer])
- [#1848] Remove sleep() from the password modules ([leofeyer])
- [#1574] Fix a glitch in the tl_settings.xlf file ([Toflar])
- [#1448] Remove the "doNotRedirectEmpty" option ([aschempp])
- [#1464] Do not allow slashes at the beginning and end of a folder alias ([leofeyer])
- [#1458] Always set host and language when generating the navigation menu ([aschempp])

[Semantic Versioning]: https://semver.org/spec/v2.0.0.html
[4.10.5]: https://github.com/contao/contao/releases/tag/4.10.5
[4.10.4]: https://github.com/contao/contao/releases/tag/4.10.4
[4.10.3]: https://github.com/contao/contao/releases/tag/4.10.3
[4.10.2]: https://github.com/contao/contao/releases/tag/4.10.2
[4.10.1]: https://github.com/contao/contao/releases/tag/4.10.1
[4.10.0]: https://github.com/contao/contao/releases/tag/4.10.0
[4.10.0-RC4]: https://github.com/contao/contao/releases/tag/4.10.0-RC4
[4.10.0-RC3]: https://github.com/contao/contao/releases/tag/4.10.0-RC3
[4.10.0-RC2]: https://github.com/contao/contao/releases/tag/4.10.0-RC2
[4.10.0-RC1]: https://github.com/contao/contao/releases/tag/4.10.0-RC1
[aschempp]: https://github.com/aschempp
[ausi]: https://github.com/ausi
[bytehead]: https://github.com/bytehead
[dmolineus]: https://github.com/dmolineus
[eS-IT]: https://github.com/eS-IT
[fritzmg]: https://github.com/fritzmg
[frontendschlampe]: https://github.com/frontendschlampe
[leofeyer]: https://github.com/leofeyer
[m-vo]: https://github.com/m-vo
[richardhj]: https://github.com/richardhj
[Toflar]: https://github.com/Toflar
[#2553]: https://github.com/contao/contao/pull/2553
[#2532]: https://github.com/contao/contao/pull/2532
[#2524]: https://github.com/contao/contao/pull/2524
[#2314]: https://github.com/contao/contao/pull/2314
[#2419]: https://github.com/contao/contao/pull/2419
[#2423]: https://github.com/contao/contao/pull/2423
[#2379]: https://github.com/contao/contao/pull/2379
[#2410]: https://github.com/contao/contao/pull/2410
[#2341]: https://github.com/contao/contao/pull/2341
[#2397]: https://github.com/contao/contao/pull/2397
[#2337]: https://github.com/contao/contao/pull/2337
[#2265]: https://github.com/contao/contao/pull/2265
[#2372]: https://github.com/contao/contao/pull/2372
[#2385]: https://github.com/contao/contao/pull/2385
[#2365]: https://github.com/contao/contao/pull/2365
[#2328]: https://github.com/contao/contao/pull/2328
[#2323]: https://github.com/contao/contao/pull/2323
[#2311]: https://github.com/contao/contao/pull/2311
[#2298]: https://github.com/contao/contao/pull/2298
[#2280]: https://github.com/contao/contao/pull/2280
[#2270]: https://github.com/contao/contao/pull/2270
[#2267]: https://github.com/contao/contao/pull/2267
[#2261]: https://github.com/contao/contao/pull/2261
[#2250]: https://github.com/contao/contao/pull/2250
[#2227]: https://github.com/contao/contao/pull/2227
[#2247]: https://github.com/contao/contao/pull/2247
[#2173]: https://github.com/contao/contao/pull/2173
[#2234]: https://github.com/contao/contao/pull/2234
[#2226]: https://github.com/contao/contao/pull/2226
[#2184]: https://github.com/contao/contao/pull/2184
[#2172]: https://github.com/contao/contao/pull/2172
[#2167]: https://github.com/contao/contao/pull/2167
[#2151]: https://github.com/contao/contao/pull/2151
[#2145]: https://github.com/contao/contao/pull/2145
[#2144]: https://github.com/contao/contao/pull/2144
[#2136]: https://github.com/contao/contao/pull/2136
[#2017]: https://github.com/contao/contao/pull/2017
[#2021]: https://github.com/contao/contao/pull/2021
[#2019]: https://github.com/contao/contao/pull/2019
[#2071]: https://github.com/contao/contao/pull/2071
[#2098]: https://github.com/contao/contao/pull/2098
[#2065]: https://github.com/contao/contao/pull/2065
[#2064]: https://github.com/contao/contao/pull/2064
[#2069]: https://github.com/contao/contao/pull/2069
[#2068]: https://github.com/contao/contao/pull/2068
[#2060]: https://github.com/contao/contao/pull/2060
[#2062]: https://github.com/contao/contao/pull/2062
[#2061]: https://github.com/contao/contao/pull/2061
[#2059]: https://github.com/contao/contao/pull/2059
[#2048]: https://github.com/contao/contao/pull/2048
[#2041]: https://github.com/contao/contao/pull/2041
[#2042]: https://github.com/contao/contao/pull/2042
[#1984]: https://github.com/contao/contao/pull/1984
[#2026]: https://github.com/contao/contao/pull/2026
[#2024]: https://github.com/contao/contao/pull/2024
[#2018]: https://github.com/contao/contao/pull/2018
[#1985]: https://github.com/contao/contao/pull/1985
[#2008]: https://github.com/contao/contao/pull/2008
[#2009]: https://github.com/contao/contao/pull/2009
[#2000]: https://github.com/contao/contao/pull/2000
[#1986]: https://github.com/contao/contao/pull/1986
[#1987]: https://github.com/contao/contao/pull/1987
[#1990]: https://github.com/contao/contao/pull/1990
[#1972]: https://github.com/contao/contao/pull/1972
[#1975]: https://github.com/contao/contao/pull/1975
[#1973]: https://github.com/contao/contao/pull/1973
[#1976]: https://github.com/contao/contao/pull/1976
[#1964]: https://github.com/contao/contao/pull/1964
[#1970]: https://github.com/contao/contao/pull/1970
[#1971]: https://github.com/contao/contao/pull/1971
[#1969]: https://github.com/contao/contao/pull/1969
[#1961]: https://github.com/contao/contao/pull/1961
[#1960]: https://github.com/contao/contao/pull/1960
[#1516]: https://github.com/contao/contao/pull/1516
[#1528]: https://github.com/contao/contao/pull/1528
[#1830]: https://github.com/contao/contao/pull/1830
[#1463]: https://github.com/contao/contao/pull/1463
[#1753]: https://github.com/contao/contao/pull/1753
[#1768]: https://github.com/contao/contao/pull/1768
[#1756]: https://github.com/contao/contao/pull/1756
[#1864]: https://github.com/contao/contao/pull/1864
[#1902]: https://github.com/contao/contao/pull/1902
[#1904]: https://github.com/contao/contao/pull/1904
[#1893]: https://github.com/contao/contao/pull/1893
[#1679]: https://github.com/contao/contao/pull/1679
[#1890]: https://github.com/contao/contao/pull/1890
[#1896]: https://github.com/contao/contao/pull/1896
[#1798]: https://github.com/contao/contao/pull/1798
[#1829]: https://github.com/contao/contao/pull/1829
[#1850]: https://github.com/contao/contao/pull/1850
[#1382]: https://github.com/contao/contao/pull/1382
[#1881]: https://github.com/contao/contao/pull/1881
[#1446]: https://github.com/contao/contao/pull/1446
[#1778]: https://github.com/contao/contao/pull/1778
[#1870]: https://github.com/contao/contao/pull/1870
[#1869]: https://github.com/contao/contao/pull/1869
[#1465]: https://github.com/contao/contao/pull/1465
[#1826]: https://github.com/contao/contao/pull/1826
[#1652]: https://github.com/contao/contao/pull/1652
[#1650]: https://github.com/contao/contao/pull/1650
[#1509]: https://github.com/contao/contao/pull/1509
[#1624]: https://github.com/contao/contao/pull/1624
[#1518]: https://github.com/contao/contao/pull/1518
[#1526]: https://github.com/contao/contao/pull/1526
[#1506]: https://github.com/contao/contao/pull/1506
[#1501]: https://github.com/contao/contao/pull/1501
[#1468]: https://github.com/contao/contao/pull/1468
[#1483]: https://github.com/contao/contao/pull/1483
[#1480]: https://github.com/contao/contao/pull/1480
[#1447]: https://github.com/contao/contao/pull/1447
[#1951]: https://github.com/contao/contao/pull/1951
[#1948]: https://github.com/contao/contao/pull/1948
[#1946]: https://github.com/contao/contao/pull/1946
[#1954]: https://github.com/contao/contao/pull/1954
[#1953]: https://github.com/contao/contao/pull/1953
[#1944]: https://github.com/contao/contao/pull/1944
[#1924]: https://github.com/contao/contao/pull/1924
[#1936]: https://github.com/contao/contao/pull/1936
[#1931]: https://github.com/contao/contao/pull/1931
[#1930]: https://github.com/contao/contao/pull/1930
[#1916]: https://github.com/contao/contao/pull/1916
[#1918]: https://github.com/contao/contao/pull/1918
[#1915]: https://github.com/contao/contao/pull/1915
[#1911]: https://github.com/contao/contao/pull/1911
[#1900]: https://github.com/contao/contao/pull/1900
[#1895]: https://github.com/contao/contao/pull/1895
[#1884]: https://github.com/contao/contao/pull/1884
[#1872]: https://github.com/contao/contao/pull/1872
[#1848]: https://github.com/contao/contao/pull/1848
[#1574]: https://github.com/contao/contao/pull/1574
[#1448]: https://github.com/contao/contao/pull/1448
[#1464]: https://github.com/contao/contao/pull/1464
[#1458]: https://github.com/contao/contao/pull/1458
