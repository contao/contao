# Changelog

This project adheres to [Semantic Versioning].

## [4.11.0-RC1] (2021-01-18)

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
[4.11.0-RC1]: https://github.com/contao/contao/releases/tag/4.11.0-RC1
[ausi]: https://github.com/ausi
[bytehead]: https://github.com/bytehead
[dmolineus]: https://github.com/dmolineus
[fritzmg]: https://github.com/fritzmg
[leofeyer]: https://github.com/leofeyer
[m-vo]: https://github.com/m-vo
[rabauss]: https://github.com/rabauss
[simonreitinger]: https://github.com/simonreitinger
[Toflar]: https://github.com/Toflar
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
