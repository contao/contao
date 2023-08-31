# Changelog

This project adheres to [Semantic Versioning].

## [5.2.2] (2023-08-31)

**Fixed issues:**

- [#6351] Add autowiring alias for PageFinder ([aschempp])

## [5.2.1] (2023-08-30)

**Fixed issues:**

- [#6329] Add the missing autowiring alias for the new `ProcessUtil` service ([Toflar])

## [5.2.0] (2023-08-15)

## [5.2.0-RC6] (2023-08-11)

**New features:**

- [#6274] Use a custom label for `tl_content.overwriteLink` ([leofeyer])

**Fixed issues:**

- [#6282] Fix labels for news and event feature operation ([aschempp])

## [5.2.0-RC5] (2023-08-04)

**Fixed issues:**

- [#6267] Adjust the "preserve metadata" logic ([leofeyer])

## [5.2.0-RC4] (2023-08-01)

## [5.2.0-RC3] (2023-07-25)

**Fixed issues:**

- [#6244] Fix the "folder ID … is not mounted" error ([ausi])
- [#6238] Correctly generate the file tree for admin users ([leofeyer])
- [#6224] Allow to pass options to the Handorgel initializer ([aschempp])

## [5.2.0-RC2] (2023-07-11)

**Fixed issues:**

- [#6202] Correctly set the APP_SECRET environment variable in the manager plugin ([leofeyer])
- [#6203] Revert 'Allow multiple label callbacks' ([leofeyer])

## [5.2.0-RC1] (2023-07-10)

**New features:**

- [#6194] Improve caching of the "date" insert tag ([Toflar])
- [#6195] New insert tags system ([ausi])
- [#6124] Implement visible root trails for DC_Folder ([Toflar])
- [#4779] New insert tags system ([ausi])
- [#6153] Rotate the chevron icon instead of replacing it ([leofeyer])
- [#6165] Deprecate using objects that have been imported via `System::import()` ([leofeyer])
- [#6164] Remove the leftover BC layer in `System::__get()` ([leofeyer])
- [#6163] Always use `System::importStatic()` for callbacks and hooks ([leofeyer])
- [#6054] Allow multiple label callbacks ([leofeyer])
- [#6156] Use `--disabled` instead of `_` for disabled icons ([leofeyer])
- [#6086] Support dark variants for all icons ([leofeyer])
- [#6128] Add image quality setting for image sizes ([ausi])
- [#5419] Use a status-aware title for toggle fields ([aschempp])
- [#5837] Add image metadata support ([ausi])
- [#6110] Use only primary languages in the default locale list ([ausi])
- [#5610] Add an "edit image size" popup link ([aschempp])
- [#5875] Add the `js_accordion` template using Handorgel.js ([aschempp])
- [#5808] Render modules and elements from templates ([aschempp])
- [#6008] Add the `USER_CAN_ACCESS_PAGE` permission ([aschempp])
- [#5849] Add the primary image of news and events to the metadata ([fritzmg])
- [#5717] Make the template selection translatable in the back end ([m-vo])
- [#5796] Stop using the combiner in the back end ([leofeyer])
- [#5807] Ship the Ajax form javascript in the template ([leofeyer])
- [#5805] Allow checking whether any field is allowed in the BackendAccessVoter ([aschempp])

**Fixed issues:**

- [#6137] Upgrade the Handorgel implementation ([aschempp])
- [#6118] Only prepend the protocol in the page finder if the hostname is not empty ([leofeyer])
- [#6119] Stop using Symfony‘s deprecated RequestMatcher class ([leofeyer])
- [#6117] Stop using the deprecated contao/image methods ([leofeyer])
- [#6085] Remove the parameters.yml file from the skeleton ([leofeyer])
- [#5794] Always show the 404 page ([aschempp])
- [#5881] Remove the inconsistent form field wrapper ([leofeyer])

[Semantic Versioning]: https://semver.org/spec/v2.0.0.html
[5.2.2]: https://github.com/contao/contao/releases/tag/5.2.2
[5.2.1]: https://github.com/contao/contao/releases/tag/5.2.1
[5.2.0]: https://github.com/contao/contao/releases/tag/5.2.0
[5.2.0-RC6]: https://github.com/contao/contao/releases/tag/5.2.0-RC6
[5.2.0-RC5]: https://github.com/contao/contao/releases/tag/5.2.0-RC5
[5.2.0-RC4]: https://github.com/contao/contao/releases/tag/5.2.0-RC4
[5.2.0-RC3]: https://github.com/contao/contao/releases/tag/5.2.0-RC3
[5.2.0-RC2]: https://github.com/contao/contao/releases/tag/5.2.0-RC2
[5.2.0-RC1]: https://github.com/contao/contao/releases/tag/5.2.0-RC1
[aschempp]: https://github.com/aschempp
[ausi]: https://github.com/ausi
[fritzmg]: https://github.com/fritzmg
[leofeyer]: https://github.com/leofeyer
[m-vo]: https://github.com/m-vo
[Toflar]: https://github.com/Toflar
[#4779]: https://github.com/contao/contao/pull/4779
[#5419]: https://github.com/contao/contao/pull/5419
[#5610]: https://github.com/contao/contao/pull/5610
[#5717]: https://github.com/contao/contao/pull/5717
[#5794]: https://github.com/contao/contao/pull/5794
[#5796]: https://github.com/contao/contao/pull/5796
[#5805]: https://github.com/contao/contao/pull/5805
[#5807]: https://github.com/contao/contao/pull/5807
[#5808]: https://github.com/contao/contao/pull/5808
[#5837]: https://github.com/contao/contao/pull/5837
[#5849]: https://github.com/contao/contao/pull/5849
[#5875]: https://github.com/contao/contao/pull/5875
[#5881]: https://github.com/contao/contao/pull/5881
[#6008]: https://github.com/contao/contao/pull/6008
[#6054]: https://github.com/contao/contao/pull/6054
[#6085]: https://github.com/contao/contao/pull/6085
[#6086]: https://github.com/contao/contao/pull/6086
[#6110]: https://github.com/contao/contao/pull/6110
[#6117]: https://github.com/contao/contao/pull/6117
[#6118]: https://github.com/contao/contao/pull/6118
[#6119]: https://github.com/contao/contao/pull/6119
[#6124]: https://github.com/contao/contao/pull/6124
[#6128]: https://github.com/contao/contao/pull/6128
[#6137]: https://github.com/contao/contao/pull/6137
[#6153]: https://github.com/contao/contao/pull/6153
[#6156]: https://github.com/contao/contao/pull/6156
[#6163]: https://github.com/contao/contao/pull/6163
[#6164]: https://github.com/contao/contao/pull/6164
[#6165]: https://github.com/contao/contao/pull/6165
[#6194]: https://github.com/contao/contao/pull/6194
[#6195]: https://github.com/contao/contao/pull/6195
[#6202]: https://github.com/contao/contao/pull/6202
[#6203]: https://github.com/contao/contao/pull/6203
[#6224]: https://github.com/contao/contao/pull/6224
[#6238]: https://github.com/contao/contao/pull/6238
[#6244]: https://github.com/contao/contao/pull/6244
[#6267]: https://github.com/contao/contao/pull/6267
[#6274]: https://github.com/contao/contao/pull/6274
[#6282]: https://github.com/contao/contao/pull/6282
[#6329]: https://github.com/contao/contao/pull/6329
[#6351]: https://github.com/contao/contao/pull/6351
