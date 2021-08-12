# Changelog

This project adheres to [Semantic Versioning].

## [4.12.0-RC3] (2021-08-12)

**Security fixes:**

- Prevent privilege escalation with the form generator ([CVE-2021-37627])
- Prevent PHP file inclusion via insert tags ([CVE-2021-37626])
- Prevent XSS via HTML attributes in the back end ([CVE-2021-35955])

**Fixed issues:**

- [#3295] Fix the "iflng" and "ifnlng" insert tags ([ausi])
- [#3276] Check catalogue for country or locale translations ([ausi])
- [#3268] Fix a type error in the event list module ([leofeyer])
- [#3270] Always refresh the template hierarchy in the debug:contao-twig command ([m-vo])
- [#3269] Show native language suffix only in user profile ([ausi])

## [4.12.0-RC2] (2021-08-04)

**New features:**

- [#3228] Require twig/extra-bundle in the managed edition ([m-vo])

**Fixed issues:**

- [#3258] Deprecated the return of null response in fragments ([aschempp])
- [#3146] Deprecate the insert tag flag "|absolute" ([ausi])
- [#3250] Add missing SchemaOrgRuntime service declaration ([m-vo])
- [#3219] Track theme templates in the template hierarchy ([m-vo])
- [#3213] Support dynamic Twig includes/extends ([m-vo])
- [#3247] Unify the "numberOfItems" translations ([leofeyer])
- [#3243] Fix a func_get_arg() value error ([ausi])
- [#3233] Fix session service check in AddSessionBagsPass ([fritzmg])
- [#3215] Make Contao compatible with Symfony 5.3 ([ausi])
- [#3201] Only create proxy templates for '@Contao' namespaced templates ([m-vo])
- [#3204] Implement a contao_ variant of the html_attr escaper ([m-vo])
- [#3203] Fix extending multiple levels of Contao templates ([ausi])
- [#3202] Sanity check template names in FigureRenderer ([m-vo])
- [#3196] Support multiple JSON+LD objects in the search document ([qzminski])
- [#3195] Do not prefix our JSON-LD context ([ausi])

## [4.12.0-RC1] (2021-07-14)

**Security fixes:**

- Prevent insert tag injection in forms ([CVE-2020-25768])
- Prevent XSS in the system log ([CVE-2021-35210])

**New features:**

- [#2988] Add Twig support ([m-vo])
- [#3138] Use intl for languages and countries ([ausi])
- [#3151] Enforce the MySQL strict mode ([m-vo])
- [#3167] Use schema.org JSON-LD in the faq-bundle ([Toflar])
- [#3155] Use schema.org JSON-LD in the calendar-bundle ([Toflar])
- [#3156] Use schema.org JSON-LD in the breadcrumb module ([Toflar])
- [#2305] Use Locale IDs for tl_page.language ([aschempp])
- [#3103] Allow to exclude fields in the file manager ([leofeyer])
- [#3124] Use a security voter to check the member groups ([aschempp])
- [#3135] Handle protected pages in the "quicklink" module ([leofeyer])
- [#3119] Use schema.org JSON-LD in the news-bundle ([Toflar])
- [#3120] Beautify the JSON-LD by sorting by key ([Toflar])
- [#3110] Add the `rawPlainText()` and `rawHtmlToPlainText()` template helper methods ([Toflar])
- [#3100] Use HTTPS for all schema.org links ([leofeyer])
- [#3107] Add the default WebPage schema.org data again ([Toflar])
- [#2962] Centralize the JSON-LD metadata management ([Toflar])
- [#3102] Allow direct links to public files in the file picker ([leofeyer])
- [#3088] Add a guests group to the list of allowed groups ([leofeyer])
- [#3084] Rename the `web` folder to `public` ([leofeyer])
- [#3082] Remove the technical details from the default search template ([leofeyer])
- [#3080] Reduce memory consumption in search ([ausi])
- [#3066] Rework the response context ([Toflar])
- [#3053] Add a page controller for root pages ([aschempp])
- [#3076] Add a license field to the file metadata ([Toflar])
- [#3052] Remove the password confirmation field ([leofeyer])
- [#3055] Add an enhanced Markdown content element ([Toflar])
- [#3056] Deprecate already moved config options ([ausi])
- [#3057] Sort routes by region if no preferred language matches ([aschempp])
- [#3014] Ensure clean response context and unify meta handling ([Toflar])
- [#3051] Remove the "addWizardClass" workaround ([leofeyer])
- [#3019] Dispatch an event to define metadata inside the FigureBuilder ([m-vo])
- [#3017] Handle UUIDs in the file metadata class ([m-vo])
- [#2975] Add the response context ([Toflar])
- [#2804] Drop the LegacyFigureBuilderTrait ([m-vo])
- [#2917] Remove the search results cache ([leofeyer])
- [#2801] Add JSON+LD metadata to the search index ([Toflar])
- [#2724] Support extracting the canonical URI from a document ([Toflar])
- [#2733] Make the FigureBuilder more fail tolerant ([m-vo])
- [#2734] Enable accessing Figure in all templates ([m-vo])

**Fixed issues:**

- [#3182] Simplify Controller::addImageToTemplate tests ([m-vo])
- [#3170] Add JSON-LD support for Twig ([m-vo])
- [#3175] Only warn if the DB server is not running in strict mode ([leofeyer])
- [#3164] Deprecate the getSearchablePages() method ([aschempp])
- [#3166] Revert 'Lazy-load the `rootFallbackLanguage` property' ([leofeyer])
- [#3163] Remove left-over itemprop usages ([leofeyer])
- [#3150] Always use the security voter to check member groups ([aschempp])
- [#3161] Unify the schema.org helper methods ([leofeyer])
- [#3165] Prevent an "undefined property" error in the Model class ([leofeyer])
- [#3162] Remove two left-over itemprop attributes ([Toflar])
- [#3154] Do not override the global language in some front end modules ([leofeyer])
- [#3148] Filter "guests only" pages to restore backwards compatibility ([leofeyer])
- [#3147] Add back the "guests" checkbox ([leofeyer])
- [#3141] Deprecate using tabindex values greater than 0 ([leofeyer])
- [#3136] Make sure that deserialized groups are always an array ([leofeyer])
- [#3075] Harden `FigureBuilder::from()` against invalid types ([m-vo])
- [#3073] Allow null in FigureBuilder#from() ([m-vo])
- [#3010] Remove a left-over call to the LegacyFigureBuilderTrait ([m-vo])
- [#2713] Mention removal of MediaElement.js in the UPGRADE.md file ([fritzmg])

[Semantic Versioning]: https://semver.org/spec/v2.0.0.html
[4.12.0-RC3]: https://github.com/contao/contao/releases/tag/4.12.0-RC3
[4.12.0-RC2]: https://github.com/contao/contao/releases/tag/4.12.0-RC2
[4.12.0-RC1]: https://github.com/contao/contao/releases/tag/4.12.0-RC1
[CVE-2021-37627]: https://github.com/contao/contao/security/advisories/GHSA-hq5m-mqmx-fw6m
[CVE-2021-37626]: https://github.com/contao/contao/security/advisories/GHSA-r6mv-ppjc-4hgr
[CVE-2021-35955]: https://github.com/contao/contao/security/advisories/GHSA-hr3h-x6gq-rqcp
[CVE-2020-25768]: https://github.com/contao/contao/security/advisories/GHSA-f7wm-x4gw-6m23
[CVE-2021-35210]: https://github.com/contao/contao/security/advisories/GHSA-h58v-c6rf-g9f7
[aschempp]: https://github.com/aschempp
[ausi]: https://github.com/ausi
[fritzmg]: https://github.com/fritzmg
[leofeyer]: https://github.com/leofeyer
[m-vo]: https://github.com/m-vo
[qzminski]: https://github.com/qzminski
[Toflar]: https://github.com/Toflar
[#3295]: https://github.com/contao/contao/pull/3295
[#3276]: https://github.com/contao/contao/pull/3276
[#3268]: https://github.com/contao/contao/pull/3268
[#3270]: https://github.com/contao/contao/pull/3270
[#3269]: https://github.com/contao/contao/pull/3269
[#3228]: https://github.com/contao/contao/pull/3228
[#3258]: https://github.com/contao/contao/pull/3258
[#3146]: https://github.com/contao/contao/pull/3146
[#3250]: https://github.com/contao/contao/pull/3250
[#3219]: https://github.com/contao/contao/pull/3219
[#3213]: https://github.com/contao/contao/pull/3213
[#3247]: https://github.com/contao/contao/pull/3247
[#3243]: https://github.com/contao/contao/pull/3243
[#3233]: https://github.com/contao/contao/pull/3233
[#3215]: https://github.com/contao/contao/pull/3215
[#3201]: https://github.com/contao/contao/pull/3201
[#3204]: https://github.com/contao/contao/pull/3204
[#3203]: https://github.com/contao/contao/pull/3203
[#3202]: https://github.com/contao/contao/pull/3202
[#3196]: https://github.com/contao/contao/pull/3196
[#3195]: https://github.com/contao/contao/pull/3195
[#2988]: https://github.com/contao/contao/pull/2988
[#3138]: https://github.com/contao/contao/pull/3138
[#3151]: https://github.com/contao/contao/pull/3151
[#3167]: https://github.com/contao/contao/pull/3167
[#3155]: https://github.com/contao/contao/pull/3155
[#3156]: https://github.com/contao/contao/pull/3156
[#2305]: https://github.com/contao/contao/pull/2305
[#3103]: https://github.com/contao/contao/pull/3103
[#3124]: https://github.com/contao/contao/pull/3124
[#3135]: https://github.com/contao/contao/pull/3135
[#3119]: https://github.com/contao/contao/pull/3119
[#3120]: https://github.com/contao/contao/pull/3120
[#3110]: https://github.com/contao/contao/pull/3110
[#3100]: https://github.com/contao/contao/pull/3100
[#3107]: https://github.com/contao/contao/pull/3107
[#2962]: https://github.com/contao/contao/pull/2962
[#3102]: https://github.com/contao/contao/pull/3102
[#3088]: https://github.com/contao/contao/pull/3088
[#3084]: https://github.com/contao/contao/pull/3084
[#3082]: https://github.com/contao/contao/pull/3082
[#3080]: https://github.com/contao/contao/pull/3080
[#3066]: https://github.com/contao/contao/pull/3066
[#3053]: https://github.com/contao/contao/pull/3053
[#3076]: https://github.com/contao/contao/pull/3076
[#3052]: https://github.com/contao/contao/pull/3052
[#3055]: https://github.com/contao/contao/pull/3055
[#3056]: https://github.com/contao/contao/pull/3056
[#3057]: https://github.com/contao/contao/pull/3057
[#3014]: https://github.com/contao/contao/pull/3014
[#3051]: https://github.com/contao/contao/pull/3051
[#3019]: https://github.com/contao/contao/pull/3019
[#3017]: https://github.com/contao/contao/pull/3017
[#2975]: https://github.com/contao/contao/pull/2975
[#2804]: https://github.com/contao/contao/pull/2804
[#2917]: https://github.com/contao/contao/pull/2917
[#2801]: https://github.com/contao/contao/pull/2801
[#2724]: https://github.com/contao/contao/pull/2724
[#2733]: https://github.com/contao/contao/pull/2733
[#2734]: https://github.com/contao/contao/pull/2734
[#3182]: https://github.com/contao/contao/pull/3182
[#3170]: https://github.com/contao/contao/pull/3170
[#3175]: https://github.com/contao/contao/pull/3175
[#3164]: https://github.com/contao/contao/pull/3164
[#3166]: https://github.com/contao/contao/pull/3166
[#3163]: https://github.com/contao/contao/pull/3163
[#3150]: https://github.com/contao/contao/pull/3150
[#3161]: https://github.com/contao/contao/pull/3161
[#3165]: https://github.com/contao/contao/pull/3165
[#3162]: https://github.com/contao/contao/pull/3162
[#3154]: https://github.com/contao/contao/pull/3154
[#3148]: https://github.com/contao/contao/pull/3148
[#3147]: https://github.com/contao/contao/pull/3147
[#3141]: https://github.com/contao/contao/pull/3141
[#3136]: https://github.com/contao/contao/pull/3136
[#3075]: https://github.com/contao/contao/pull/3075
[#3073]: https://github.com/contao/contao/pull/3073
[#3010]: https://github.com/contao/contao/pull/3010
[#2713]: https://github.com/contao/contao/pull/2713
