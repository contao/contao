# Contao core bundle change log

# 4.5.13 (2018-08-27)

 * Do not merge the session cookie header (see #11, #29).
 * Update the list of countries (see #12).

## 4.5.11 (2018-08-13)

 * Prefix numeric aliases with `id-` (see contao/core-bundle#1598).
 * Correctly set the back end headline for custom actions (see contao/newsletter-bundle#23). 
 * Remove support for deprecated user password hashes (see contao/core-bundle#1608).

## 4.5.10 (2018-06-26)

 * Make the session listener compatible with Symfony 3.4.12.

## 4.5.9 (2018-06-18)

 * Show the 404 page if there is no match in the set of potential pages (see contao/core-bundle#1522).
 * Correctly calculate the nodes when initializing the picker (see contao/core-bundle#1535).
 * Do not re-send the activation mail if auto-registration is disabled (see contao/core-bundle#1526).
 * Remove the app_dev.php/ fragment when generating sitemaps (see contao/core-bundle#1520).
 * Show the 404 page if a numeric aliases has no url suffix (see contao/core-bundle#1508).
 * Fix the schema.org markup so the breadcrumb is linked to the webpage (see contao/core-bundle#1527).
 * Reduce memory consumption during sitemap generation (see contao/core-bundle#1549).
 * Correctly handle spaces when opening nodes in the file picker (see contao/core-bundle#1449).
 * Also check the InnoDB file system (see contao/installation-bundle#91).
 * Disable the maintenance mode for local requests (see contao/manager-bundle#1492).
 * Correctly blacklist unsubscribed recipients (see contao/newsletter-bundle#21).
 * Use a given e-mail address in the unsubscribe module (see contao/newsletter-bundle#12).
 * Delete old subscriptions if the new e-mail address exists (see contao/newsletter-bundle#19).

## 4.5.8 (2018-04-18)

 * Fix an XSS vulnerability in the system log (see CVE-2018-10125).
 * Correctly highlight all keywords in the search results (see contao/core-bundle#1461).
 * Log unknown insert tag (flags) in the system log (see contao/core-bundle#1182).

## 4.5.7 (2018-04-04)

 * Correctly hide empty custom layout sections (see contao/core-bundle#1115).
 * Correctly generate the login target URLs in the back end (see contao/core-bundle#1432).
 * Preserve the container directory when purging the cache.
 * Warm up the cache in the Composer script handler (see contao/manager-bundle#59).
 * Correctly duplicate recipients if a channel is duplicated (see contao/newsletter-bundle#15).

## 4.5.6 (2018-03-06)

 * Use the DCA information to determine the index length (see contao/installation-bundle#88).

## 4.5.5 (2018-03-06)

 * Support using InnoDB without the `innodb_large_prefix` option.
 * Correctly track modified fields in the `Model` class (see contao/core-bundle#1290).
 * Use the normalized package versions for the CDN scripts (see contao/core-bundle#1391).
 * Catch the DriverException if the database connection fails (see contao/managed-edition#27).

## 4.5.4 (2018-02-14)

 * Preserve custom CSS classes in the back end navigation (see contao/core-bundle#1357).

## 4.5.3 (2018-01-23)

 * Correctly select folders as gallery source (see contao/core-bundle#1328).
 * Use a given e-mail address in the unsubscribe module (see contao/newsletter-bundle#12).

## 4.5.2 (2018-01-12)

 * Use the `BINARY` flag instead of `COLLATE utf8mb4_bin` (see contao/core-bundle#1286).
 * Use a custom toggle parameter for content elements (see contao/core-bundle#1291).
 * Dynamically register the user session response listener (see contao/core-bundle#1293).
 * Redirect to the last page visited if a back end session expires.
 * Do not parse @@version to determine the database vendor (see contao/installation-bundle#84).

## 4.5.1 (2018-01-04)

 * Set the correct CSS classes in the back end navigation (see contao/core-bundle#1278).
 * Correctly handle authentication through the Symfony firewall entry point (see contao/core-bundle#1275).
 * Make services public that we need to access directly.
 * Check all `innodb_large_prefix` requirements in the install tool (see contao/installation-bundle#80).
 * Use the table options instead of the default table options to compare engine and collation.

## 4.5.0 (2017-12-28)

 * Apply the schema filter in the DCA schema provider.

## 4.5.0-RC1 (2017-12-12)

 * Use the Symfony security component for authentication (see contao/core-bundle#685).
 * Check the MySQL version and the configured database options.

## 4.5.0-beta3 (2017-12-04)

 * Use InnoDB as default storage engine (see contao/core-bundle#188).
 * Use "utf8mb4" as default charset for MySQL (see contao/core-bundle#113).
 * Add roles and ARIA landmarks in the back end (see contao/core-bundle#768).
 * Add all the player options to the YouTube element (see contao/core-bundle#938).
 * Add the toggle icon of the parent record in parent view (see contao/core#2266).
 * Decrease the number of DB queries by reusing the page model (see contao/core-bundle#1090).
 * Add the "event address" field and update the microdata tags (see contao/calendar-bundle#18).
 * Fix the length of the e-mail field in the dynamic comments form.
 * Also check the table engine and collation during database migration.
 * Improve the microdata support.
 * Standardize the length of the e-mail fields.

## 4.5.0-beta2 (2017-11-24)

 * Display form fieldsets as wrappers in the back end view (see contao/core-bundle#1102).
 * Replace the back end "limit width" option with a "fullscreen" option (see contao/core-bundle#1082).
 * Support translating the date format strings (see contao/core-bundle#872).
 * Add the "require an item" option to the site structure (see contao/core#8361).
 * Support adding external JavaScripts to a page layout (see contao/core-bundle#690).
 * Support media:content tags when generating feeds (see contao/news-bundle#7).
 * Support wildcards in the "iflng" and "ifnlng" insert tags (see contao/core#8313).
 * Add the FilesModel::findByPid() method (see contao/core-bundle#925).
 * Support sorting enclosures (see contao/calendar-bundle#16).
 * Show the default value of overwritable fields as placeholder (see contao/core-bundle#1120).
 * Also use STRONG instead of SPAN in the breadcrumb menu (see contao/core-bundle#1154).
 * Use the Symfony assets component for the Contao assets (see contao/core-bundle#1165).
 * Do not log known exceptions with a pretty error screen (see contao/core-bundle#1139).
 * Add the teaser image as media:content in the RSS/Atom feeds (see contao/news-bundle#7).
 * Allow to sort news items by date, headline or randomly (see contao/news-bundle#13).

## 4.5.0-beta1 (2017-11-06)

 * Use ausi/slug-generator to auto-generate aliases (see contao/core-bundle#1016).
 * Allow to register hooks listeners as tagged services (see contao/core-bundle#1094).
 * Use a service to build the back end menu (see contao/core-bundle#1066).
 * Add the FilesModel::findMultipleFoldersByFolder() method (see contao/core#7942).
 * Add the fragment registry and the fragment renderers (see contao/core-bundle#700).
 * Use a double submit cookie instead of storing the CSRF token in the PHP session (see contao/core-bundle#1065).
 * Decorate the Symfony translator so it reads the Contao labels (see contao/core-bundle#1072).
 * Improve the locale handling (see contao/core-bundle#1064).
 * Extended reporting if the script handler fails (see contao/manager-bundle#27).
 * Set prepend_locale to false (see contao/core-bundle#785).
 * Change the maintenance lock file path (see contao/core-bundle#728).
 * Add basic security (see contao/standard-edition#54).
