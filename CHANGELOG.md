# Changelog

This project adheres to [Semantic Versioning].

## [5.4.9] (2025-01-03)

**Fixed issues:**

- [#7841] Register folders, pages and nodes as tooltip targets ([zoglo])

## [5.4.8] (2024-12-10)

**Fixed issues:**

- [#7741] Make the SERP preview script compatible with Turbo ([fritzmg])

## [5.4.7] (2024-11-28)

**Fixed issues:**

- [#7739] Fix a JS performance issue related to the registration of tooltips ([m-vo])

## [5.4.6] (2024-11-22)

**Fixed issues:**

- [#7736] Disable Turbo in `MODE_PARENT` content section ([fritzmg])
- [#7737] Revert 'Add the `turbo-root` meta tag to the back end' ([fritzmg])

## [5.4.5] (2024-11-21)

**Fixed issues:**

- [#7687] Adjust the tooltip performance ([zoglo])
- [#7723] Add an ALTCHA noscript warning message ([CMSworker])
- [#7724] Canonicalize purge paths ([fritzmg])
- [#7734] Make the slots concept compatible with Twig 3.15 ([m-vo])

## [5.4.4] (2024-10-23)

**Fixed issues:**

- [#7663] Correctly set the ACE editor height ([leofeyer])
- [#7596] Fix the tooltips on mobile devices ([fritzmg])
- [#7597] Add the `turbo-root` meta tag to the back end ([aschempp])
- [#7603] Add `display: grid` for subpalettes on Ajax requests ([zoglo])
- [#7546] Add the missing `widget-group` classes ([fritzmg])

## [5.4.3] (2024-09-17)

**Security fixes:**

- [CVE-2024-45398]: Remote command execution through file uploads
- [CVE-2024-45612]: Insert tag injection via canonical URLs

## [5.4.2] (2024-09-12)

**Fixed issues:**

- [#7503] Always break out of Turbo frames when missing ([fritzmg])
- [#7500] Switch to a stable ALTCHA version ([leofeyer])
- [#7499] Disable Turbo for the 2FA backup codes form ([leofeyer])
- [#7493] Fix the loading time of the code editor ([leofeyer])

## [5.4.1] (2024-08-23)

**Fixed issues:**

- [#7471] Fix the line height of the ellipsis containers ([leofeyer])
- [#7458] Add a Stimulus controller to handle back end tooltips  ([zoglo])
- [#7456] Use `Turbo.cache.exemptPageFromCache()` in the SERP preview script ([leofeyer])
- [#7451] Do not initialize Chosen twice in the module wizard ([leofeyer])
- [#7446] Ensure that `$this->style` is never `null` ([leofeyer])
- [#7449] Disable Turbo on theme import forms ([zoglo])

## [5.4.0] (2024-08-15)

**Fixed issues:**

- [#7441] Deprecate the service annotations ([leofeyer])

## [5.4.0-RC4] (2024-08-13)

**Fixed issues:**

- [#7419] Use `<turbo-frame>` for `DataContainer::edit()` and fix other Turbo issues ([fritzmg])
- [#7437] Fix the Twig deprecations ([ausi])

## [5.4.0-RC3] (2024-08-06)

**Fixed issues:**

- [#7408] Fix several Turbo issues ([m-vo])
- [#7410] Allow ALTCHA version 0.7 ([leofeyer])
- [#7395] Simplify the automatic ACE editor height ([m-vo])
- [#7406] Revert 'Move the `assets` folder to `public/assets`' ([leofeyer])
- [#7394] Add a Stimulus controller to configure TinyMCE instances ([m-vo])
- [#7393] Add a note about `$GLOBALS['objPage']` to the DEPRECATED.md file ([leofeyer])

## [5.4.0-RC2] (2024-07-30)

**Fixed issues:**

- [#7387] Load the TinyMCE and ACE scripts within `be_main` ([zoglo])
- [#7384] Show a warning if ALTCHA is used with an insecure connection ([leofeyer])
- [#7373] Fix the `contao-setup` command ([leofeyer])
- [#7378] Disable Turbo on `editAll` and `overrideAll` forms ([fritzmg])

## [5.4.0-RC1] (2024-07-08)

**New features:**

- [#7209] Add an "ARIA label" field to the navigation module ([leofeyer])
- [#7011] Add basic support for `hotwired/turbo` in the back end ([m-vo])
- [#7273] Store the back end popup session bag under a different storage key ([fritzmg])
- [#6999] Add Twig slots ([m-vo])
- [#7094] Move the `assets` folder to `public/assets` ([leofeyer])
- [#7054] Add an ALTCHA form field to the form generator ([markocupic])
- [#7299] Add more spacing in the back end views ([leofeyer])
- [#7301] Update the file icons in the file manager ([leofeyer])
- [#7279] Allow Symfony 7 ([Toflar])
- [#7142] Update Monolog to version 3 ([Wusch])
- [#7278] Use Lucide icons in the back end ([leofeyer])
- [#6731] Add sitemap information to the `tl_page.robots` help text ([stefansl])
- [#7249] Allow TinyMCE 7 ([leofeyer])
- [#7238] Use `HtmlAttributes` for `fe_page` ([fritzmg])
- [#7218] Use the `attr()` method in templates ([leofeyer])
- [#7087] Render widget groups in the back end with CSS grid ([zoglo])
- [#7034] Add a rich text Twig component ([m-vo])
- [#7000] Replace `$GLOBALS['objPage']` in the model argument resolver ([leofeyer])
- [#7004] Replace `$GLOBALS['objPage']` in the filesystem loader ([leofeyer])
- [#7001] Replace `$GLOBALS['objPage']` in the fragment handler ([leofeyer])
- [#6997] Add the `PageFinder::getCurrentPage()` method ([leofeyer])
- [#6998] Fallback to the current request in the scope matcher ([leofeyer])
- [#6994] Remove the `InterestCohortListener` ([bytehead])
- [#6846] Allow to configure the components-dir ([richardhj])

**Fixed issues:**

- [#7326] Use the minified version of the ALTCHA scripts ([leofeyer])
- [#7318] Remove the redundant `m12` CSS class ([leofeyer])
- [#7302] Add the missing dark icons ([leofeyer])
- [#7298] Fix the icon sizes ([leofeyer])
- [#7231] Use CSS grid to align checkboxes and their labels and drag handles ([leofeyer])
- [#7229] Fix the `.nogrid` backwards compatibility layer ([leofeyer])

[Semantic Versioning]: https://semver.org/spec/v2.0.0.html
[5.4.9]: https://github.com/contao/contao/releases/tag/5.4.9
[5.4.8]: https://github.com/contao/contao/releases/tag/5.4.8
[5.4.7]: https://github.com/contao/contao/releases/tag/5.4.7
[5.4.6]: https://github.com/contao/contao/releases/tag/5.4.6
[5.4.5]: https://github.com/contao/contao/releases/tag/5.4.5
[5.4.4]: https://github.com/contao/contao/releases/tag/5.4.4
[5.4.3]: https://github.com/contao/contao/releases/tag/5.4.3
[5.4.2]: https://github.com/contao/contao/releases/tag/5.4.2
[5.4.1]: https://github.com/contao/contao/releases/tag/5.4.1
[5.4.0]: https://github.com/contao/contao/releases/tag/5.4.0
[5.4.0-RC4]: https://github.com/contao/contao/releases/tag/5.4.0-RC4
[5.4.0-RC3]: https://github.com/contao/contao/releases/tag/5.4.0-RC3
[5.4.0-RC2]: https://github.com/contao/contao/releases/tag/5.4.0-RC2
[5.4.0-RC1]: https://github.com/contao/contao/releases/tag/5.4.0-RC1
[CVE-2024-45398]: https://github.com/contao/contao/security/advisories/GHSA-vm6r-j788-hjh5
[CVE-2024-45612]: https://github.com/contao/contao/security/advisories/GHSA-2xpq-xp6c-5mgj
[aschempp]: https://github.com/aschempp
[ausi]: https://github.com/ausi
[bytehead]: https://github.com/bytehead
[CMSworker]: https://github.com/CMSworker
[fritzmg]: https://github.com/fritzmg
[leofeyer]: https://github.com/leofeyer
[m-vo]: https://github.com/m-vo
[markocupic]: https://github.com/markocupic
[richardhj]: https://github.com/richardhj
[stefansl]: https://github.com/stefansl
[Toflar]: https://github.com/Toflar
[Wusch]: https://github.com/Wusch
[zoglo]: https://github.com/zoglo
[#6731]: https://github.com/contao/contao/pull/6731
[#6846]: https://github.com/contao/contao/pull/6846
[#6994]: https://github.com/contao/contao/pull/6994
[#6997]: https://github.com/contao/contao/pull/6997
[#6998]: https://github.com/contao/contao/pull/6998
[#6999]: https://github.com/contao/contao/pull/6999
[#7000]: https://github.com/contao/contao/pull/7000
[#7001]: https://github.com/contao/contao/pull/7001
[#7004]: https://github.com/contao/contao/pull/7004
[#7011]: https://github.com/contao/contao/pull/7011
[#7034]: https://github.com/contao/contao/pull/7034
[#7054]: https://github.com/contao/contao/pull/7054
[#7087]: https://github.com/contao/contao/pull/7087
[#7094]: https://github.com/contao/contao/pull/7094
[#7142]: https://github.com/contao/contao/pull/7142
[#7209]: https://github.com/contao/contao/pull/7209
[#7218]: https://github.com/contao/contao/pull/7218
[#7229]: https://github.com/contao/contao/pull/7229
[#7231]: https://github.com/contao/contao/pull/7231
[#7238]: https://github.com/contao/contao/pull/7238
[#7249]: https://github.com/contao/contao/pull/7249
[#7273]: https://github.com/contao/contao/pull/7273
[#7278]: https://github.com/contao/contao/pull/7278
[#7279]: https://github.com/contao/contao/pull/7279
[#7298]: https://github.com/contao/contao/pull/7298
[#7299]: https://github.com/contao/contao/pull/7299
[#7301]: https://github.com/contao/contao/pull/7301
[#7302]: https://github.com/contao/contao/pull/7302
[#7318]: https://github.com/contao/contao/pull/7318
[#7326]: https://github.com/contao/contao/pull/7326
[#7373]: https://github.com/contao/contao/pull/7373
[#7378]: https://github.com/contao/contao/pull/7378
[#7384]: https://github.com/contao/contao/pull/7384
[#7387]: https://github.com/contao/contao/pull/7387
[#7393]: https://github.com/contao/contao/pull/7393
[#7394]: https://github.com/contao/contao/pull/7394
[#7395]: https://github.com/contao/contao/pull/7395
[#7406]: https://github.com/contao/contao/pull/7406
[#7408]: https://github.com/contao/contao/pull/7408
[#7410]: https://github.com/contao/contao/pull/7410
[#7419]: https://github.com/contao/contao/pull/7419
[#7437]: https://github.com/contao/contao/pull/7437
[#7441]: https://github.com/contao/contao/pull/7441
[#7446]: https://github.com/contao/contao/pull/7446
[#7449]: https://github.com/contao/contao/pull/7449
[#7451]: https://github.com/contao/contao/pull/7451
[#7456]: https://github.com/contao/contao/pull/7456
[#7458]: https://github.com/contao/contao/pull/7458
[#7471]: https://github.com/contao/contao/pull/7471
[#7493]: https://github.com/contao/contao/pull/7493
[#7499]: https://github.com/contao/contao/pull/7499
[#7500]: https://github.com/contao/contao/pull/7500
[#7503]: https://github.com/contao/contao/pull/7503
[#7546]: https://github.com/contao/contao/pull/7546
[#7596]: https://github.com/contao/contao/pull/7596
[#7597]: https://github.com/contao/contao/pull/7597
[#7603]: https://github.com/contao/contao/pull/7603
[#7663]: https://github.com/contao/contao/pull/7663
[#7687]: https://github.com/contao/contao/pull/7687
[#7723]: https://github.com/contao/contao/pull/7723
[#7724]: https://github.com/contao/contao/pull/7724
[#7734]: https://github.com/contao/contao/pull/7734
[#7736]: https://github.com/contao/contao/pull/7736
[#7737]: https://github.com/contao/contao/pull/7737
[#7739]: https://github.com/contao/contao/pull/7739
[#7741]: https://github.com/contao/contao/pull/7741
[#7841]: https://github.com/contao/contao/pull/7841
