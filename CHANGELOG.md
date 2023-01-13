# Changelog

This project adheres to [Semantic Versioning].

## [5.1.0-RC1] (2023-01-13)

**New features:**

- [#4847] Add a new feed reader implementation ([bezin])
- [#5682] Allow to pass an array of allowed attributes to Input::stripTags() ([leofeyer])
- [#5672] Add more back end grid classes ([leofeyer])
- [#5673] Add an attributes_callback for DCA fields ([aschempp])
- [#5671] Allow a minimum amount of workers for autoscaling ([Toflar])
- [#3694] Add the URI and page ID to log entries ([SeverinGloeckle])
- [#5427] Check the administrator email address ([fritzmg])
- [#5405] Introduce background workers ([Toflar])
- [#5631] Implement a dark scheme toggle ([aschempp])
- [#5368] Enable the login rate limit ([bytehead])
- [#5598] Also minify the core.js and mootao.js scripts with Webpack ([leofeyer])
- [#5031] Add a dark mode for the back end ([leofeyer])
- [#4936] Support the native date input type for text fields in the form generator ([fritzmg])
- [#5307] Add a confirmation message to forms and provide Ajax out of the box ([qzminski])
- [#4898] Add error handling to the form data processing ([rabauss])
- [#5425] Allow sorting DCA fields ascending and descending ([aschempp])
- [#5417] Add a button to copy multiple records and paste them multiple times ([aschempp])
- [#5591] Remove localconfig.disableCron in favor of a new zero config approach ([Toflar])
- [#5602] Adjust the name of the default guests group ([leofeyer])
- [#5116] Add the MemberActivationMailEvent ([fritzmg])
- [#5478] Add the `#[\SensitiveParameter]` attribute ([ausi])
- [#5371] Allow to set the locale in `Template::trans` ([fritzmg])
- [#5609] Improve the content elements view ([leofeyer])
- [#5607] Use CSS variables for all colors and CSS classes instead of inline styles ([leofeyer])
- [#5592] Add a favorites menu in the back end ([leofeyer])
- [#5594] Also add the new "idempotent actions" logic to DC_Folder ([leofeyer])
- [#5406] Add stimulus controllers in the back end ([aschempp])
- [#5554] Add support for async CLI cron jobs ([Toflar])
- [#5461] Disable the request token check for idempotent actions ([aschempp])
- [#5364] Allow defining a default search field ([leofeyer])
- [#5403] Add a user option to not collapse content elements ([aschempp])
- [#5379] Use sendfile for local files downloads ([m-vo])
- [#5359] Change the default value for tl_layout.viewport ([leofeyer])
- [#5347] Add a markdown help text ([leofeyer])
- [#5304] Add a lock overlay for protected articles ([leofeyer])

**Fixed issues:**

- [#5683] Fix the skippable cronjobs ([fritzmg])
- [#5680] Add isRequired() for desired_size and max settings on workers ([Toflar])
- [#5679] Add a default value for the worker "min" configuration ([Toflar])
- [#5670] Do not add the request token to idempotent actions ([leofeyer])
- [#5666] Skip cron execution for minutely cron and messenger cron in web scope ([Toflar])
- [#5653] Fix the tree indentation ([leofeyer])
- [#5652] Only hide the dark/light theme icons via CSS ([leofeyer])
- [#5647] Ensure Doctrine connections are closed after message handling ([Toflar])
- [#5646] Use enumNode instead of custom validation ([Toflar])
- [#5597] Correctly open and close tree nodes if there is a filter ([leofeyer])
- [#5593] Fix the `aria-hidden` attribute in the tips.js file ([leofeyer])

[Semantic Versioning]: https://semver.org/spec/v2.0.0.html
[5.1.0-RC1]: https://github.com/contao/contao/releases/tag/5.1.0-RC1
[aschempp]: https://github.com/aschempp
[ausi]: https://github.com/ausi
[bezin]: https://github.com/bezin
[bytehead]: https://github.com/bytehead
[fritzmg]: https://github.com/fritzmg
[leofeyer]: https://github.com/leofeyer
[m-vo]: https://github.com/m-vo
[qzminski]: https://github.com/qzminski
[rabauss]: https://github.com/rabauss
[SeverinGloeckle]: https://github.com/SeverinGloeckle
[Toflar]: https://github.com/Toflar
[#4847]: https://github.com/contao/contao/pull/4847
[#5682]: https://github.com/contao/contao/pull/5682
[#5672]: https://github.com/contao/contao/pull/5672
[#5673]: https://github.com/contao/contao/pull/5673
[#5671]: https://github.com/contao/contao/pull/5671
[#3694]: https://github.com/contao/contao/pull/3694
[#5427]: https://github.com/contao/contao/pull/5427
[#5405]: https://github.com/contao/contao/pull/5405
[#5631]: https://github.com/contao/contao/pull/5631
[#5368]: https://github.com/contao/contao/pull/5368
[#5598]: https://github.com/contao/contao/pull/5598
[#5031]: https://github.com/contao/contao/pull/5031
[#4936]: https://github.com/contao/contao/pull/4936
[#5307]: https://github.com/contao/contao/pull/5307
[#4898]: https://github.com/contao/contao/pull/4898
[#5425]: https://github.com/contao/contao/pull/5425
[#5417]: https://github.com/contao/contao/pull/5417
[#5591]: https://github.com/contao/contao/pull/5591
[#5602]: https://github.com/contao/contao/pull/5602
[#5116]: https://github.com/contao/contao/pull/5116
[#5478]: https://github.com/contao/contao/pull/5478
[#5371]: https://github.com/contao/contao/pull/5371
[#5609]: https://github.com/contao/contao/pull/5609
[#5607]: https://github.com/contao/contao/pull/5607
[#5592]: https://github.com/contao/contao/pull/5592
[#5594]: https://github.com/contao/contao/pull/5594
[#5406]: https://github.com/contao/contao/pull/5406
[#5554]: https://github.com/contao/contao/pull/5554
[#5461]: https://github.com/contao/contao/pull/5461
[#5364]: https://github.com/contao/contao/pull/5364
[#5403]: https://github.com/contao/contao/pull/5403
[#5379]: https://github.com/contao/contao/pull/5379
[#5359]: https://github.com/contao/contao/pull/5359
[#5347]: https://github.com/contao/contao/pull/5347
[#5304]: https://github.com/contao/contao/pull/5304
[#5683]: https://github.com/contao/contao/pull/5683
[#5680]: https://github.com/contao/contao/pull/5680
[#5679]: https://github.com/contao/contao/pull/5679
[#5670]: https://github.com/contao/contao/pull/5670
[#5666]: https://github.com/contao/contao/pull/5666
[#5653]: https://github.com/contao/contao/pull/5653
[#5652]: https://github.com/contao/contao/pull/5652
[#5647]: https://github.com/contao/contao/pull/5647
[#5646]: https://github.com/contao/contao/pull/5646
[#5597]: https://github.com/contao/contao/pull/5597
[#5593]: https://github.com/contao/contao/pull/5593
