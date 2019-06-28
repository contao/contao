# Change log

## DEV

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
 * Add a splash image to the Vimeo and YouTube elements (see #300).
 * Make the notification bell counter more visible (see #289).
 * Replace the `BE_PAGE_OFFSET` cookie with a sessionStorage entry (see #467).
 * Replace the `BE_CELL_SIZE` cookie with a localStorage entry (see #468).
 * Get rid of the header replay bundle (see #365).
 * Rename `app.php` to `index.php` (see #362).
 * Use JWT to configure the front controller (see #152).
 * Use the SQL default value where possible (see #340).
