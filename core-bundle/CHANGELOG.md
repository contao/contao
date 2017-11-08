# Contao core bundle change log

### DEV

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
