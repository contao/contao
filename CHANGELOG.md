# Changelog

This project adheres to [Semantic Versioning].

## [4.9.0-RC2] (2020-02-11)

**Fixed defects:**

- [#1292] Revert the "contao_backend_switch" route name change ([richardhj])
- [#1293] Update the back end keyboard shortcuts link ([leofeyer])
- [#1291] Hide the member widget if searching protected sites is disabled ([leofeyer])
- [#1286] Fix the front end preview URLs in the back end ([leofeyer])
- [#1267] Fix possible null value in AuthenticationSuccessHandler ([bytehead])
- [#1275] Move the PreviewAuthenticationListener to the core-bundle ([bytehead])
- [#1257] Allow DCA filter definitions without placeholder values ([fritzmg])
- [#1289] Improve the post update/install CLI hint ([Toflar])
- [#1282] Correctly publish new folders ([leofeyer])
- [#1283] Use wikimedia/less.php instead of oyejorge/less.php ([leofeyer])
- [#1284] Add z-index:1 to the paste hint ([leofeyer])
- [#1230] Add a "title" attribute to the debug mode menu item ([xchs])
- [#1255] Do not use a relative font for the preview bar ([leofeyer])
- [#1222] Do not try to register Contao 3 classes as services ([aschempp])
- [#1246] Fix possible null value access in BackupCodeManager ([bytehead])
- [#1237] Set the DB password to null if empty ([leofeyer])
- [#1217] Correctly encode the DATABASE_URL parameter ([richardhj])
- [#1232] Add the "url" parameter to the DropZone options ([bytehead])
- [#1225] Correctly determine the Ajax URL now that our forms no longer have an action ([leofeyer])
- [#1223] Add a crawl queue maintenance job ([Toflar])
- [#1221] Set the row format in the default table options ([leofeyer])

## [4.9.0-RC1] (2020-01-18)

**New features:**

- [#559] Add trusted devices for 2FA ([bytehead])
- [#1165] Automatically load services in the src/ directory ([aschempp])
- [#1184] Add backup code functionality for 2FA ([bytehead], [aschempp])
- [#1098] Add a cron service and command with cron expressions ([fritzmg])
- [#1180] Use spl_object_id() instead of spl_object_hash() ([Toflar])
- [#1178] Rebuild the login process ([bytehead], [aschempp])
- [#1057] Add a back end maintenance task for the crawler ([Toflar])
- [#580] Get pagetree picker url parameters via own method ([rabauss])
- [#579] Get filetree picker url parameters via own method ([rabauss])
- [#521] Add a range field and support steps in text fields ([fritzmg])
- [#1154] Get universal picker url parameters via own method ([rabauss])
- [#989] Rework the front end preview ([richardhj])
- [#709] Add a migrations command ([ausi])
- [#581] Compile navigation row via own method ([rabauss])
- [#860] Use the Knp menu for the back end header menu ([leofeyer])
- [#1068] Implement a broken link checker ([Toflar])
- [#1132] Turn event listeners with more than one method into to subscribers ([leofeyer])
- [#705] Allow to limit the content element and form field types ([leofeyer])
- [#1121] Pass the module model to the navigation templates ([leofeyer])
- [#1125] Simplify the event registration ([leofeyer])
- [#1129] Update the node modules and run the Gulp task ([leofeyer])
- [#1122] Add the WEBP icon to the mimetypes mapper ([leofeyer])
- [#1123] Do not strip forms in the ModuleArticle::generatePdf() method ([leofeyer])
- [#1124] Rename "account settings" to "group settings" for groups ([leofeyer])
- [#1115] Test the service arguments more accurately ([leofeyer])
- [#1101] Refactor the back end main menu so it becomes a regular Knp menu ([leofeyer])
- [#714] Add a universal table picker ([aschempp])
- [#1085] Add support for the new bundle structure ([aschempp])
- [#1078] Correctly reset the necessary services ([aschempp])
- [#1094] Upgrade to PHPStan 0.12 ([leofeyer])
- [#1080] Replace Guzzle with Symfony's HttpClient ([Toflar])
- [#1086] Ignore the .github folder when installing from dist ([leofeyer])
- [#718] Use cache strategy to merge fragment caching into main page ([aschempp])
- [#1063] Add support for invokable listeners and method validation ([aschempp])
- [#603] Add an abstract controller for common service tasks ([aschempp])
- [#1055] Hide the meta data field when editing folders ([leofeyer])
- [#1045] Add @internal to what is not covered by our BC promise ([leofeyer])
- [#1053] Do not add the X-Forwarded-Host in Environment::url() anymore ([leofeyer])
- [#1058] Do not use Chosen in the meta wizard anymore ([leofeyer])
- [#1056] Cleanup the SubscriberResult class ([Toflar])
- [#1052] Add referrerpolicy="no-referrer" when loading assets via CDN ([leofeyer])
- [#954] Set the "accept" attribute in the upload form field ([bohnmedia])
- [#985] Implement the contao:crawl command ([Toflar])
- [#852] Add a command to debug fragments ([aschempp])
- [#1034] Allow to configure the HttpCache trace level using an environment variable ([Toflar])
- [#1039] Use the object type instead of @param object now that we have PHP 7.2 ([leofeyer])
- [#1027] Automatically upgrade new password hashes ([Toflar])
- [#983] Support configuring the SearchIndexListener behaviour ([Toflar])
- [#621] Add a command to debug manager plugins ([aschempp])
- [#1012] Update to Symfony 4.4 ([leofeyer])
- [#990] Explicitly enable the required PHP extensions ([leofeyer])
- [#976] Replace the ./run script with Composer ([leofeyer])
- [#968] Adjust the help text of the start/stop fields ([leofeyer])
- [#840] Use the default template path ([m-vo])
- [#955] Do not require league/uri anymore ([leofeyer])
- [#639] Fix model relations when using entities ([Tastaturberuf])
- [#946] Stop using the deprecated Doctrine DBAL methods ([leofeyer])
- [#948] Remove the app folder if there are no more files in it ([leofeyer])
- [#887] Clear invalid URLs using the new search indexer abstraction ([Toflar])
- [#945] Remove tests that are now always skipped ([leofeyer])
- [#943] Update to PHP 7.2, PHPUnit 8 and Doctrine DBAL 2.10 ([leofeyer])
- [#810] Automatically load the services.yml file if it exists ([leofeyer])
- [#730] Implement a search indexer abstraction ([Toflar])
- [#604] Pass the mime type to the download element links ([Toflar])
- [#672] Optimize DBAFS file sync ([m-vo])
- [#605] Use environment variables for app config ([aschempp])
- [#768] Support using env(DATABASE_URL) ([leofeyer])
- [#762] Do not install the tests with "prefer-dist" ([leofeyer])
- [#776] Simplify registering custom fragment types ([aschempp])
- [#717] Dynamically add robots.txt and favicon.ico per root page ([Toflar])
- [#703] Add support for lazy loading images ([ausi])

**Fixed defects:**

- [#1212] Tag the old version update classes as migrations ([leofeyer])
- [#1213] Change the allowed status codes for preview bar injection ([richardhj])
- [#1210] Show preview toolbar on error pages ([richardhj])
- [#1211] Fix issues found by the PhpStorm code inspector ([leofeyer])
- [#1209] Refine the new labels ([leofeyer])
- [#806] Add info for more configuration options ([fritzmg])
- [#1199] Support .yaml config files and the routes.yaml file ([aschempp])
- [#1201] Remove all form actions ([aschempp])
- [#1203] Improve the clickable area of the fieldset legends ([leofeyer])
- [#1177] Toggle DCA legends on their legend text only ([Tastaturberuf])
- [#1196] Warn if a user or user group has permission to import themes ([leofeyer])
- [#1197] Rework the module labels ([leofeyer])
- [#1202] Always allow root pages in custom navigation modules ([leofeyer])
- [#1195] Use the two factor factory constants in the unit tests ([leofeyer])
- [#1189] Fix the security.yml code in the README file ([bytehead])
- [#1190] Do not merge sub-requests without Cache-Control header ([aschempp])
- [#1191] Do not pollute the session with a target path ([aschempp])
- [#1194] Show the correct error message if a wrong 2FA token is entered ([bytehead])
- [#1188] Remove a superfluous argument in the RememberMeRepository class ([fritzmg])
- [#1181] Always set the connection in the RememberMeRepository class ([leofeyer])
- [#1173] Generate all languages when warming the cache ([leofeyer])
- [#1179] Do not decode entities in page names ([leofeyer])
- [#1175] Add the ce_access database migration ([leofeyer])
- [#1176] Disable the MakeResponsePrivateListener for non-Contao requests ([Toflar])
- [#1048] Improve the "purge search results cache" label ([Toflar])
- [#1171] Clean up the Escargot integration ([Toflar])
- [#1118] Override the authentication listener to validate FORM_SUBMIT ([aschempp])
- [#1169] Fix the SERP preview alias field and missing primary key ([aschempp])
- [#1164] Redirect to FE preview page after login and ensure integrity of target links ([Toflar])
- [#1162] Remove the obsolete container.autowiring.strict_mode parameter ([aschempp])
- [#1159] Fix the contao.image.sizes keys ([leofeyer])
- [#1158] Use addArgument() and addOption() instead of setDefinition() ([leofeyer])
- [#1155] Fix parameter names which are not in snake case ([leofeyer])
- [#1157] Update the badges in the README.md file ([leofeyer])
- [#1153] Fix the user and user group filters ([leofeyer])
- [#1151] Fix the description of the contao:crawl command ([leofeyer])
- [#1149] Fix the tl_files.source label ([leofeyer])
- [#1150] Rename "Column" to "Layout section" ([leofeyer])
- [#1148] Rename "File location" to "Path" ([leofeyer])
- [#1147] Add "contao.editable_files" to the configuration ([leofeyer])
- [#1145] Re-add prestissimo to shivammathur/setup-php ([leofeyer])
- [#1131] Fix a method name in the TwoFactorFrontendListenerTest class ([bytehead])
- [#1128] Use the version callbacks to backup and restore file contents ([leofeyer])
- [#1126] Remove the registerCommands() methods ([leofeyer])
- [#1092] Fix the SERP preview ([leofeyer])
- [#1120] Only check $this->admin in the BackendUser class ([leofeyer])
- [#1116] Replace "web/" with "contao.web_dir" ([leofeyer])
- [#1113] Fix the search focus outline in Safari ([leofeyer])
- [#1114] Fix a typo in the FrontendTemplate class ([leofeyer])
- [#1100] Do not use array_insert to inject modules and menu items ([leofeyer])
- [#1102] Make sure we have the correct type when a search document is created ([Toflar])
- [#1103] Fixed incorrect service tag ([Toflar])
- [#1095] Also test if the number of service tags matches ([leofeyer])
- [#1097] Clean up the Composer conflicts ([leofeyer])
- [#1054] Fix the page type descriptions ([leofeyer])
- [#1046] Fix the height of the meta wizard button ([leofeyer])
- [#1050] Use Throwable instead of Exception in the exception and error listeners ([leofeyer])
- [#1033] Disable auto cache control of the Symfony SessionListener ([Toflar])
- [#1013] BackendAccessVoter ensure string when checking for supported attribute ([AndreasA])
- [#1010] Fix the Doctrine platform recognition ([leofeyer])
- [#991] Replace mb_strlen() with Utf8::strlen() ([leofeyer])

[Semantic Versioning]: https://semver.org/spec/v2.0.0.html
[4.9.0-RC2]: https://github.com/contao/contao/releases/tag/4.9.0-RC2
[4.9.0-RC1]: https://github.com/contao/contao/releases/tag/4.9.0-RC1
[richardhj]: https://github.com/richardhj
[leofeyer]: https://github.com/leofeyer
[bytehead]: https://github.com/bytehead
[fritzmg]: https://github.com/fritzmg
[Toflar]: https://github.com/Toflar
[xchs]: https://github.com/xchs
[aschempp]: https://github.com/aschempp
[rabauss]: https://github.com/rabauss
[ausi]: https://github.com/ausi
[bohnmedia]: https://github.com/bohnmedia
[m-vo]: https://github.com/m-vo
[Tastaturberuf]: https://github.com/Tastaturberuf
[AndreasA]: https://github.com/AndreasA
[#1292]: https://github.com/contao/contao/pull/1292
[#1293]: https://github.com/contao/contao/pull/1293
[#1291]: https://github.com/contao/contao/pull/1291
[#1286]: https://github.com/contao/contao/pull/1286
[#1267]: https://github.com/contao/contao/pull/1267
[#1275]: https://github.com/contao/contao/pull/1275
[#1257]: https://github.com/contao/contao/pull/1257
[#1289]: https://github.com/contao/contao/pull/1289
[#1282]: https://github.com/contao/contao/pull/1282
[#1283]: https://github.com/contao/contao/pull/1283
[#1284]: https://github.com/contao/contao/pull/1284
[#1230]: https://github.com/contao/contao/pull/1230
[#1255]: https://github.com/contao/contao/pull/1255
[#1222]: https://github.com/contao/contao/pull/1222
[#1246]: https://github.com/contao/contao/pull/1246
[#1237]: https://github.com/contao/contao/pull/1237
[#1217]: https://github.com/contao/contao/pull/1217
[#1232]: https://github.com/contao/contao/pull/1232
[#1225]: https://github.com/contao/contao/pull/1225
[#1223]: https://github.com/contao/contao/pull/1223
[#1221]: https://github.com/contao/contao/pull/1221
[#559]: https://github.com/contao/contao/pull/559
[#1165]: https://github.com/contao/contao/pull/1165
[#1184]: https://github.com/contao/contao/pull/1184
[#1098]: https://github.com/contao/contao/pull/1098
[#1180]: https://github.com/contao/contao/pull/1180
[#1178]: https://github.com/contao/contao/pull/1178
[#1057]: https://github.com/contao/contao/pull/1057
[#580]: https://github.com/contao/contao/pull/580
[#579]: https://github.com/contao/contao/pull/579
[#521]: https://github.com/contao/contao/pull/521
[#1154]: https://github.com/contao/contao/pull/1154
[#989]: https://github.com/contao/contao/pull/989
[#709]: https://github.com/contao/contao/pull/709
[#581]: https://github.com/contao/contao/pull/581
[#860]: https://github.com/contao/contao/pull/860
[#1068]: https://github.com/contao/contao/pull/1068
[#1132]: https://github.com/contao/contao/pull/1132
[#705]: https://github.com/contao/contao/pull/705
[#1121]: https://github.com/contao/contao/pull/1121
[#1125]: https://github.com/contao/contao/pull/1125
[#1129]: https://github.com/contao/contao/pull/1129
[#1122]: https://github.com/contao/contao/pull/1122
[#1123]: https://github.com/contao/contao/pull/1123
[#1124]: https://github.com/contao/contao/pull/1124
[#1115]: https://github.com/contao/contao/pull/1115
[#1101]: https://github.com/contao/contao/pull/1101
[#714]: https://github.com/contao/contao/pull/714
[#1085]: https://github.com/contao/contao/pull/1085
[#1078]: https://github.com/contao/contao/pull/1078
[#1094]: https://github.com/contao/contao/pull/1094
[#1080]: https://github.com/contao/contao/pull/1080
[#1086]: https://github.com/contao/contao/pull/1086
[#718]: https://github.com/contao/contao/pull/718
[#1063]: https://github.com/contao/contao/pull/1063
[#603]: https://github.com/contao/contao/pull/603
[#1055]: https://github.com/contao/contao/pull/1055
[#1045]: https://github.com/contao/contao/pull/1045
[#1053]: https://github.com/contao/contao/pull/1053
[#1058]: https://github.com/contao/contao/pull/1058
[#1056]: https://github.com/contao/contao/pull/1056
[#1052]: https://github.com/contao/contao/pull/1052
[#954]: https://github.com/contao/contao/pull/954
[#985]: https://github.com/contao/contao/pull/985
[#852]: https://github.com/contao/contao/pull/852
[#1034]: https://github.com/contao/contao/pull/1034
[#1039]: https://github.com/contao/contao/pull/1039
[#1027]: https://github.com/contao/contao/pull/1027
[#983]: https://github.com/contao/contao/pull/983
[#621]: https://github.com/contao/contao/pull/621
[#1012]: https://github.com/contao/contao/pull/1012
[#990]: https://github.com/contao/contao/pull/990
[#976]: https://github.com/contao/contao/pull/976
[#968]: https://github.com/contao/contao/pull/968
[#840]: https://github.com/contao/contao/pull/840
[#955]: https://github.com/contao/contao/pull/955
[#639]: https://github.com/contao/contao/pull/639
[#946]: https://github.com/contao/contao/pull/946
[#948]: https://github.com/contao/contao/pull/948
[#887]: https://github.com/contao/contao/pull/887
[#945]: https://github.com/contao/contao/pull/945
[#943]: https://github.com/contao/contao/pull/943
[#810]: https://github.com/contao/contao/pull/810
[#730]: https://github.com/contao/contao/pull/730
[#604]: https://github.com/contao/contao/pull/604
[#672]: https://github.com/contao/contao/pull/672
[#605]: https://github.com/contao/contao/pull/605
[#768]: https://github.com/contao/contao/pull/768
[#762]: https://github.com/contao/contao/pull/762
[#776]: https://github.com/contao/contao/pull/776
[#717]: https://github.com/contao/contao/pull/717
[#703]: https://github.com/contao/contao/pull/703
[#1212]: https://github.com/contao/contao/pull/1212
[#1213]: https://github.com/contao/contao/pull/1213
[#1210]: https://github.com/contao/contao/pull/1210
[#1211]: https://github.com/contao/contao/pull/1211
[#1209]: https://github.com/contao/contao/pull/1209
[#806]: https://github.com/contao/contao/pull/806
[#1199]: https://github.com/contao/contao/pull/1199
[#1201]: https://github.com/contao/contao/pull/1201
[#1203]: https://github.com/contao/contao/pull/1203
[#1177]: https://github.com/contao/contao/pull/1177
[#1196]: https://github.com/contao/contao/pull/1196
[#1197]: https://github.com/contao/contao/pull/1197
[#1202]: https://github.com/contao/contao/pull/1202
[#1195]: https://github.com/contao/contao/pull/1195
[#1189]: https://github.com/contao/contao/pull/1189
[#1190]: https://github.com/contao/contao/pull/1190
[#1191]: https://github.com/contao/contao/pull/1191
[#1194]: https://github.com/contao/contao/pull/1194
[#1188]: https://github.com/contao/contao/pull/1188
[#1181]: https://github.com/contao/contao/pull/1181
[#1173]: https://github.com/contao/contao/pull/1173
[#1179]: https://github.com/contao/contao/pull/1179
[#1175]: https://github.com/contao/contao/pull/1175
[#1176]: https://github.com/contao/contao/pull/1176
[#1048]: https://github.com/contao/contao/pull/1048
[#1171]: https://github.com/contao/contao/pull/1171
[#1118]: https://github.com/contao/contao/pull/1118
[#1169]: https://github.com/contao/contao/pull/1169
[#1164]: https://github.com/contao/contao/pull/1164
[#1162]: https://github.com/contao/contao/pull/1162
[#1159]: https://github.com/contao/contao/pull/1159
[#1158]: https://github.com/contao/contao/pull/1158
[#1155]: https://github.com/contao/contao/pull/1155
[#1157]: https://github.com/contao/contao/pull/1157
[#1153]: https://github.com/contao/contao/pull/1153
[#1151]: https://github.com/contao/contao/pull/1151
[#1149]: https://github.com/contao/contao/pull/1149
[#1150]: https://github.com/contao/contao/pull/1150
[#1148]: https://github.com/contao/contao/pull/1148
[#1147]: https://github.com/contao/contao/pull/1147
[#1145]: https://github.com/contao/contao/pull/1145
[#1131]: https://github.com/contao/contao/pull/1131
[#1128]: https://github.com/contao/contao/pull/1128
[#1126]: https://github.com/contao/contao/pull/1126
[#1092]: https://github.com/contao/contao/pull/1092
[#1120]: https://github.com/contao/contao/pull/1120
[#1116]: https://github.com/contao/contao/pull/1116
[#1113]: https://github.com/contao/contao/pull/1113
[#1114]: https://github.com/contao/contao/pull/1114
[#1100]: https://github.com/contao/contao/pull/1100
[#1102]: https://github.com/contao/contao/pull/1102
[#1103]: https://github.com/contao/contao/pull/1103
[#1095]: https://github.com/contao/contao/pull/1095
[#1097]: https://github.com/contao/contao/pull/1097
[#1054]: https://github.com/contao/contao/pull/1054
[#1046]: https://github.com/contao/contao/pull/1046
[#1050]: https://github.com/contao/contao/pull/1050
[#1033]: https://github.com/contao/contao/pull/1033
[#1013]: https://github.com/contao/contao/pull/1013
[#1010]: https://github.com/contao/contao/pull/1010
[#991]: https://github.com/contao/contao/pull/991
