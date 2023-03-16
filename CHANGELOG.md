# Changelog

This project adheres to [Semantic Versioning].

## [5.1.1] (2023-03-16)

**Fixed issues:**

- [#5788] Add the TokenDeauthenticatedListener ([bytehead])
- [#5870] Explicitly set the legacy template in the TwoFactorController ([m-vo])
- [#5857] Fix the split button alignment ([leofeyer])
- [#5819] Surround the `togglePassword` images with a button ([cliffparnitzky])
- [#5840] Fix deleting multiple records ([Toflar])
- [#5838] Add missing type hints to translation classes ([ausi])
- [#5821] Remove tl_settings.doNotCollapse ([aschempp])
- [#5828] Return BinaryFileResponse when handling downloads ([m-vo])
- [#5780] Fix PHPUnit deprecation warnings ([m-vo])
- [#5803] Move the favorites voter to the correct namespace ([aschempp])

## [5.1.0] (2023-02-16)

**Fixed issues:**

- [#5760] Do not deep merge messenger workers ([Toflar])
- [#5762] Fix header notification color ([ausi])

## [5.1.0-RC3] (2023-02-09)

## [5.1.0-RC2] (2023-01-27)

**Fixed issues:**

- [#5704] Do not require overwriting the console path ([leofeyer])
- [#5714] Use the HTML sanitizer component as Twig filter ([ausi])
- [#5697] Fix the module wizard ([leofeyer])
- [#5703] Fix the Gulp watch task ([fritzmg])
- [#5702] Make `$consolePath` a required argument ([leofeyer])
- [#5695] Use two different icons for light and dark mode ([leofeyer])
- [#5691] Correctly toggle the favorites menu group ([leofeyer])
- [#5687] Move the dark mode toggle to the header bar ([leofeyer])
- [#5689] Make console_path a general Contao configuration ([Toflar])
- [#5690] Fix the icons when toggling structures in dark mode ([leofeyer])

## [5.1.0-RC1] (2023-01-13)

**New features:**

- [#4847] Add a new feed reader implementation ([bezin])
- [#5682] Allow to pass an array of allowed attributes to Input::stripTags() ([leofeyer])
- [#5672] Add more back end grid classes ([leofeyer])
- [#5673] Add an attributes_callback for DCA fields ([aschempp])
- [#5671] Allow a minimum amount of workers for autoscaling ([Toflar])
- [#3694] Add the URI and page ID to log entries ([SeverinGloeckle])
- [#5427] Check the administrator e-mail address ([fritzmg])
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
[5.1.1]: https://github.com/contao/contao/releases/tag/5.1.1
[5.1.0]: https://github.com/contao/contao/releases/tag/5.1.0
[5.1.0-RC3]: https://github.com/contao/contao/releases/tag/5.1.0-RC3
[5.1.0-RC2]: https://github.com/contao/contao/releases/tag/5.1.0-RC2
[5.1.0-RC1]: https://github.com/contao/contao/releases/tag/5.1.0-RC1
[aschempp]: https://github.com/aschempp
[ausi]: https://github.com/ausi
[bezin]: https://github.com/bezin
[bytehead]: https://github.com/bytehead
[cliffparnitzky]: https://github.com/cliffparnitzky
[fritzmg]: https://github.com/fritzmg
[leofeyer]: https://github.com/leofeyer
[m-vo]: https://github.com/m-vo
[qzminski]: https://github.com/qzminski
[rabauss]: https://github.com/rabauss
[SeverinGloeckle]: https://github.com/SeverinGloeckle
[Toflar]: https://github.com/Toflar
[#3694]: https://github.com/contao/contao/pull/3694
[#4847]: https://github.com/contao/contao/pull/4847
[#4898]: https://github.com/contao/contao/pull/4898
[#4936]: https://github.com/contao/contao/pull/4936
[#5031]: https://github.com/contao/contao/pull/5031
[#5116]: https://github.com/contao/contao/pull/5116
[#5304]: https://github.com/contao/contao/pull/5304
[#5307]: https://github.com/contao/contao/pull/5307
[#5347]: https://github.com/contao/contao/pull/5347
[#5359]: https://github.com/contao/contao/pull/5359
[#5364]: https://github.com/contao/contao/pull/5364
[#5368]: https://github.com/contao/contao/pull/5368
[#5371]: https://github.com/contao/contao/pull/5371
[#5379]: https://github.com/contao/contao/pull/5379
[#5403]: https://github.com/contao/contao/pull/5403
[#5405]: https://github.com/contao/contao/pull/5405
[#5406]: https://github.com/contao/contao/pull/5406
[#5417]: https://github.com/contao/contao/pull/5417
[#5425]: https://github.com/contao/contao/pull/5425
[#5427]: https://github.com/contao/contao/pull/5427
[#5461]: https://github.com/contao/contao/pull/5461
[#5478]: https://github.com/contao/contao/pull/5478
[#5554]: https://github.com/contao/contao/pull/5554
[#5591]: https://github.com/contao/contao/pull/5591
[#5592]: https://github.com/contao/contao/pull/5592
[#5593]: https://github.com/contao/contao/pull/5593
[#5594]: https://github.com/contao/contao/pull/5594
[#5597]: https://github.com/contao/contao/pull/5597
[#5598]: https://github.com/contao/contao/pull/5598
[#5602]: https://github.com/contao/contao/pull/5602
[#5607]: https://github.com/contao/contao/pull/5607
[#5609]: https://github.com/contao/contao/pull/5609
[#5631]: https://github.com/contao/contao/pull/5631
[#5646]: https://github.com/contao/contao/pull/5646
[#5647]: https://github.com/contao/contao/pull/5647
[#5652]: https://github.com/contao/contao/pull/5652
[#5653]: https://github.com/contao/contao/pull/5653
[#5666]: https://github.com/contao/contao/pull/5666
[#5670]: https://github.com/contao/contao/pull/5670
[#5671]: https://github.com/contao/contao/pull/5671
[#5672]: https://github.com/contao/contao/pull/5672
[#5673]: https://github.com/contao/contao/pull/5673
[#5679]: https://github.com/contao/contao/pull/5679
[#5680]: https://github.com/contao/contao/pull/5680
[#5682]: https://github.com/contao/contao/pull/5682
[#5683]: https://github.com/contao/contao/pull/5683
[#5687]: https://github.com/contao/contao/pull/5687
[#5689]: https://github.com/contao/contao/pull/5689
[#5690]: https://github.com/contao/contao/pull/5690
[#5691]: https://github.com/contao/contao/pull/5691
[#5695]: https://github.com/contao/contao/pull/5695
[#5697]: https://github.com/contao/contao/pull/5697
[#5702]: https://github.com/contao/contao/pull/5702
[#5703]: https://github.com/contao/contao/pull/5703
[#5704]: https://github.com/contao/contao/pull/5704
[#5714]: https://github.com/contao/contao/pull/5714
[#5760]: https://github.com/contao/contao/pull/5760
[#5762]: https://github.com/contao/contao/pull/5762
[#5780]: https://github.com/contao/contao/pull/5780
[#5788]: https://github.com/contao/contao/pull/5788
[#5803]: https://github.com/contao/contao/pull/5803
[#5819]: https://github.com/contao/contao/pull/5819
[#5821]: https://github.com/contao/contao/pull/5821
[#5828]: https://github.com/contao/contao/pull/5828
[#5838]: https://github.com/contao/contao/pull/5838
[#5840]: https://github.com/contao/contao/pull/5840
[#5857]: https://github.com/contao/contao/pull/5857
[#5870]: https://github.com/contao/contao/pull/5870
