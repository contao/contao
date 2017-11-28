# Contao core bundle change log

### DEV

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
