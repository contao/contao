# Change log

## 4.8.8 (2020-02-17)

 * Generate catch all routes for the 404 pages (see #1321).
 * Correctly set predefined image sizes in the news and calendar bundle (see #1331).
 * Show the field name if there is no label (see #1319).
 * Correctly save unsynchronized files and folders (see #1318).
 * Correctly show the field names in the diff view (see #1317).
 * Make the "preload" attribute optional (see #1316).
 * Correctly check the roles in the TokenChecker (see #1082).
 * Handle array SQL definitions in version comparison (see #1251).
 * Adjust the text line height (see #1215).
 * Fix compatibility with knplabs/knp-time-bundle 1.11.0 (see #1140).
 * Replace document.write() in the back end templates (see #1329).
 * Fix the initial table sorting in the "table" content element (see #1315).
 * Improve the Contao\Database\Result class (see #1287).
 * Fix the top navigation overflow (see #1302).
 * Fix checking for binary strings in the version comparison (see #1294).
 * Urlencode the path returned by the "file::uuid" insert tag (see #1268).

## 4.8.7 (2019-12-17)

 * Fix the picture_default.html5 template (see #1108).

## 4.8.6 (2019-12-17)

 * Prevent insert tag injection in the login module (see CVE-2019-19714).
 * Only check the fragment registry if the controller name is a string (see #1087).
 * Unescape parameters in the installation bundle (see #1037).
 * Fix the back end system font stack for IE 11 (see #1026).
 * Correctly show custom templates in "override all" mode (see #1007).
 * Do not add the preview script when rebuilding the search index (see #1008).
 * Add webp to the list of extensions the GDlib can handle (see #1018).
 * Add the missing fields to the logout page palette (see #1006).
 * Use a strict check to determine whether a label is false (see #953).
 * Do not trigger warnings for fractions with rounding errors (see #935).
 * Prevent information disclosure in the back end (see CVE-2019-19712).
 * Prevent unrestricted file uploads (see CVE-2019-19745).
 * Set img width/height attributes as an aspect ratio (see #940).
 * Always warm the English language cache (see #1040).
 * Check if a template exists when inheriting templates (see #1016).
 * Use the status code 307 to redirect on the logout page (see #1041).
 * Sort the custom layout sections by their position (see #1042).
 * Correctly trigger kernel response events when handling exceptions (see #1020).
 * Do not catch Swift exceptions when submitting forms (see #1017).
 * Correctly compare order fields in the diff view (see #1002).

## 4.8.5 (2019-11-04)

 * Also support the `form_textfield_` prefix for custom text field templates (see #828).
 * Reduce the number of DB queries in the picture factory (see #921).
 * Hide the breadcrumb menu if the node is not within the given path (see #888).
 * Also export the media type(s) when exporting style sheets (see #905).
 * Only hide newsletter channels without redirect page in the web modules (see #907).
 * Quote the identifiers in the back end filter menu (see #906).
 * Use a monospace font in the diff view (see #904).
 * If there are no unsynchronized folders, do not show an info message (see #897).
 * Distinguish between XML and HTML sitemap (see #879).
 * Re-index array of modules after unset keys to prevent inconsistency (see #834).

## 4.8.4 (2019-10-01)

 * Correctly render the paste buttons when moving files in the file manager (see #802).
 * Correctly redirect to the last page visited in the login module (see #569).
 * Only add the min/max attributes to numeric text fields (see #774).
 * Prevent regular users from enabling the template editor for themselves (see #749).
 * Use the robots meta data to determine whether to add a page to the XML sitemap (see #501).
 * Do not versionize the file name and path (see #694).
 * Update the comments notification URL if it has changed (see #373).
 * Hide the "generate aliases" button if the alias field has not been enabled (see #771).
 * Show only the active columns in the module wizard (see #765).
 * Fix the pagination menu in the versions overview (see #752).
 * Reset unique fields when restoring a version (see #698).

## 4.8.3 (2019-09-12)

 * Consider the "disablePermissionChecks" attribute in the user modules (see #751).
 * Update the country list to CLDR35.1 (see #734).
 * Do not load the cache kernel in dev environment (see #712).
 * Fix missing labels and fields with identical labels in the show view (see #742).
 * Support hyphens in custom template names (see #731).
 * Add the `preview.php` fragment to all links in the site structure (see #723).

## 4.8.2 (2019-09-05)

 * Always render deferred images in HTML emails (see #693).
 * Do not add the min/max attributes if they are zero (see #668).
 * Handle renamed files in the version overview (see #671).
 * Hide the username if the initial version is auto-generated (see #664).
 * Set the e-mail priority if it has been given (see #608).
 * Also show the breadcrumb menu if there are no results (see #660).
 * Correctly replace literal insert tags (see #670).
 * Increase the alias field lengths (see #678).
 * Retain origId in chained alias elements (see #635).
 * Check if the theme preview image exists (see #636).
 * Correctly show the templates in the section wizard (see #677).

## 4.8.1 (2019-08-22)

 * Hide the minlength/maxlength fields for numeric fields (see #655).
 * Correctly re-add deleted languages to the meta wizard drop-down menu (see #620).
 * Bypass maintenance mode for image requests (see #648).
 * Validate allowed characters in image size names (see #634).

## 4.8.0 (2019-08-15)

 * Ignore the --no-debug option when creating the console (see #626).
 * Check for dynamic row format more consistently (see #628).
 * Automatically generate response from template in fragment controllers (see #622).
 * Update contao/image to version 1.0 (see #624).

## 4.8.0-RC2 (2019-07-30)

 * Use the security helper instead of the token storage (see #609).
 * Do not show pretty error screens for admin exceptions (see #596).
 * Use Symfony security to check access in the front end preview (see #595).
 * Also make the response private if an Authorization header is present (see #594).
 * Set the request and translator locale in case of an exception (see #453).
 * Add separate fields for the `min` and `max` attributes in the form generator (see #437).

## 4.8.0-RC1 (2019-07-15)

 * Track profiler messages in the Contao translator (see #544).
 * Add lightboxPicture in addition to imageHref (see #561).
 * Add two-factor authentication in the front end (see #363).
 * Move the default image densities into the page layout (see #545).
 * Add support for image format conversion (see #552).
 * Make the insert tags in picker providers configurable (see #450).
 * Add the "minimum keyword length" field to the search module (see #274).
 * Adjust the container CSS class in the "video/audio" element based on the media type (see #441).
 * Autorotate images based on their EXIF metadata (see #529).
 * Add a configuration option to define predefined image sizes (see #537).
 * Make the FAQ record accessible in the FAQ reader template (see #221).
 * Always create a new version if something changes, even if the form has errors (see #237).
 * Add a news list option to show featured news first (see #371).
 * Use the current firewall token if it matches the context (see #513).
 * Support Argon2 password hashing (see #536).
 * Extend the end date for unlimited event occurrences (see #510).
 * Use generic labels in DCAs (see #532).
 * Add deferred image resizing (see #354).
 * Disable CSRF if the request has no cookies (see #515).
 * Add expiration based persistent rememberme tokens (see #483).
 * Rework caching for proxies (see #389).
 * Add a command to debug a DCA (see #490).
 * Support clearing any proxy cache in the maintenance module (see #173).
 * Remove the Google+ syndication links (see #484).
 * Remove outdated Contao components, which are no longer required (see #332).
 * Add a splash image to the Vimeo and YouTube elements (see #300).
 * Make the notification bell counter more visible (see #289).
 * Replace the `BE_PAGE_OFFSET` cookie with a sessionStorage entry (see #467).
 * Replace the `BE_CELL_SIZE` cookie with a localStorage entry (see #468).
 * Get rid of the header replay bundle (see #365).
 * Rename `app.php` to `index.php` (see #362).
 * Use JWT to configure the front controller (see #152).
 * Use the SQL default value where possible (see #340).
