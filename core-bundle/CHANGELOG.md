# Contao core bundle change log

### DEV

 * Preserve custom CSS classes in the back end navigation (see #1357).

### 4.5.3 (2018-01-23)

 * Correctly select folders as gallery source (see #1328).

### 4.5.2 (2018-01-12)

 * Use the `BINARY` flag instead of `COLLATE utf8mb4_bin` (see #1286).
 * Use a custom toggle parameter for content elements (see #1291).
 * Use array_key_exists() when tracking modified model fields (see #1290).
 * Dynamically register the user session response listener (see #1293).
 * Redirect to the last page visited if a back end session expires.

### 4.5.1 (2018-01-04)

 * Set the correct CSS classes in the back end navigation (see #1278).
 * Correctly handle authentication through the Symfony firewall entry point (see #1275).
 * Make services public that we need to access directly.

### 4.5.0 (2017-12-28)

 * Apply the schema filter in the DCA schema provider.

### 4.5.0-RC1 (2017-12-12)

 * Use the Symfony security component for authentication (see #685).

### 4.5.0-beta3 (2017-12-04)

 * Use InnoDB as default storage engine (see #188).
 * Use "utf8mb4" as default charset for MySQL (see #113).
 * Add roles and ARIA landmarks in the back end (see #768).
 * Add all the player options to the YouTube element (see #938).
 * Add the toggle icon of the parent record in parent view (see contao/core#2266).
 * Decrease the number of DB queries by reusing the page model (see #1090).

### 4.5.0-beta2 (2017-11-24)

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

### 4.5.0-beta1 (2017-11-06)

 * Use ausi/slug-generator to auto-generate aliases (see #1016).
 * Allow to register hooks listeners as tagged services (see #1094).
 * Use a service to build the back end menu (see #1066).
 * Add the FilesModel::findMultipleFoldersByFolder() method (see contao/core#7942).
 * Add the fragment registry and the fragment renderers (see #700).
 * Use a double submit cookie instead of storing the CSRF token in the PHP session (see #1065).
 * Decorate the Symfony translator so it reads the Contao labels (see #1072).
 * Improve the locale handling (see #1064).
