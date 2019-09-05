# Change log

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
