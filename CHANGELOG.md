# Changelog

This project adheres to [Semantic Versioning].

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
- [#1485] Run PHPStan on level 4 in CI ([bytehead])
- [#1476] Enable PHPStan level 4 ([bytehead])
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
[4.10.0-RC2]: https://github.com/contao/contao/releases/tag/4.10.0-RC2
[4.10.0-RC1]: https://github.com/contao/contao/releases/tag/4.10.0-RC1
[aschempp]: https://github.com/aschempp
[ausi]: https://github.com/ausi
[bytehead]: https://github.com/bytehead
[dmolineus]: https://github.com/dmolineus
[eS-IT]: https://github.com/eS-IT
[fritzmg]: https://github.com/fritzmg
[leofeyer]: https://github.com/leofeyer
[m-vo]: https://github.com/m-vo
[richardhj]: https://github.com/richardhj
[Toflar]: https://github.com/Toflar
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
[#1485]: https://github.com/contao/contao/pull/1485
[#1476]: https://github.com/contao/contao/pull/1476
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
