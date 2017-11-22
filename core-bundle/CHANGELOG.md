# Contao core bundle change log

### DEV

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
