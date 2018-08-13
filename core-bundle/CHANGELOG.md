# Contao core bundle change log

## DEV

 * Prefix numeric aliases with `id-` (see #1598).
 * Correctly set the back end headline for custom actions (see contao/newsletter-bundle#23). 
 * Remove support for deprecated user password hashes (see #1608).

## 4.5.10 (2018-06-26)

 * Make the session listener compatible with Symfony 3.4.12.

## 4.5.9 (2018-06-18)

 * Show the 404 page if there is no match in the set of potential pages (see #1522).
 * Correctly calculate the nodes when initializing the picker (see #1535).
 * Do not re-send the activation mail if auto-registration is disabled (see #1526).
 * Remove the app_dev.php/ fragment when generating sitemaps (see #1520).
 * Show the 404 page if a numeric aliases has no url suffix (see #1508).
 * Fix the schema.org markup so the breadcrumb is linked to the webpage (see #1527).
 * Reduce memory consumption during sitemap generation (see #1549).
 * Correctly handle spaces when opening nodes in the file picker (see #1449).
 * Also check the InnoDB file system (see contao/installation-bundle#91).

## 4.5.8 (2018-04-18)

 * Fix an XSS vulnerability in the system log (see CVE-2018-10125).
 * Correctly highlight all keywords in the search results (see #1461).
 * Log unknown insert tag (flags) in the system log (see #1182).

## 4.5.7 (2018-04-04)

 * Correctly hide empty custom layout sections (see #1115).
 * Correctly generate the login target URLs in the back end (see #1432).

## 4.5.6 (2018-03-06)

 * Use the DCA information to determine the index length (see contao/installation-bundle#88).

## 4.5.5 (2018-03-06)

 * Support using InnoDB without the `innodb_large_prefix` option.
 * Correctly track modified fields in the `Model` class (see #1290).
 * Use the normalized package versions for the CDN scripts (see #1391).

## 4.5.4 (2018-02-14)

 * Preserve custom CSS classes in the back end navigation (see #1357).

## 4.5.3 (2018-01-23)

 * Correctly select folders as gallery source (see #1328).

## 4.5.2 (2018-01-12)

 * Use the `BINARY` flag instead of `COLLATE utf8mb4_bin` (see #1286).
 * Use a custom toggle parameter for content elements (see #1291).
 * Dynamically register the user session response listener (see #1293).
 * Redirect to the last page visited if a back end session expires.

## 4.5.1 (2018-01-04)

 * Set the correct CSS classes in the back end navigation (see #1278).
 * Correctly handle authentication through the Symfony firewall entry point (see #1275).
 * Make services public that we need to access directly.

## 4.5.0 (2017-12-28)

 * Apply the schema filter in the DCA schema provider.

## 4.5.0-RC1 (2017-12-12)

 * Use the Symfony security component for authentication (see #685).

## 4.5.0-beta3 (2017-12-04)

 * Use InnoDB as default storage engine (see #188).
 * Use "utf8mb4" as default charset for MySQL (see #113).
 * Add roles and ARIA landmarks in the back end (see #768).
 * Add all the player options to the YouTube element (see #938).
 * Add the toggle icon of the parent record in parent view (see contao/core#2266).
 * Decrease the number of DB queries by reusing the page model (see #1090).

## 4.5.0-beta2 (2017-11-24)

 * Display form fieldsets as wrappers in the back end view (see #1102).
 * Replace the back end "limit width" option with a "fullscreen" option (see #1082).
 * Support translating the date format strings (see #872).
 * Add the "require an item" option to the site structure (see contao/core#8361).
 * Support adding external JavaScripts to a page layout (see #690).
 * Support media:content tags when generating feeds (see contao/news-bundle#7).
 * Support wildcards in the "iflng" and "ifnlng" insert tags (see contao/core#8313).
 * Add the FilesModel::findByPid() method (see #925).
 * Support sorting enclosures (see contao/calendar-bundle#16).
 * Show the default value of overwritable fields as placeholder (see #1120).
 * Also use STRONG instead of SPAN in the breadcrumb menu (see #1154).
 * Use the Symfony assets component for the Contao assets (see #1165).
 * Do not log known exceptions with a pretty error screen (see #1139).

## 4.5.0-beta1 (2017-11-06)

 * Use ausi/slug-generator to auto-generate aliases (see #1016).
 * Allow to register hooks listeners as tagged services (see #1094).
 * Use a service to build the back end menu (see #1066).
 * Add the FilesModel::findMultipleFoldersByFolder() method (see contao/core#7942).
 * Add the fragment registry and the fragment renderers (see #700).
 * Use a double submit cookie instead of storing the CSRF token in the PHP session (see #1065).
 * Decorate the Symfony translator so it reads the Contao labels (see #1072).
 * Improve the locale handling (see #1064).
