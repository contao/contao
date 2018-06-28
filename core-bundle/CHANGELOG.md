# Contao core bundle change log

## DEV

 * Remove the "number of columns" field from the login module (see #1577).
 * Also use the file meta data in the download element (see #1459).
 * Purge the system log by default after 7 days (see #1512).
 * Use "noreferrer noopener" for links that open in a new window (see #1125).
 * Do not store IP addresses in tl_log (see #1512).
 * Append the module ID to the form field IDs to prevent duplicate IDs (see #1493).
 * Add the page picker to the meta fields in file manager (see #1568).
 * Remove the "flash movie" front end module.
 * Add extended video support (see #1348).
 * Pass all search result data to the search template (see #1558).
 * Auto-clear the session form data (see #1550).
 * Add abstract controllers for fragment content elements and front end modules (see #1376).
 * Handle manifest.json files in public bundle folders (see #1510).
 * Introduce auto cache tag invalidation on DCAs for better DX (see #1478).
 * Throw a "page not found" exception if a download file does not exist (see contao/core#8375).
 * Allow multi-root and multi-page searches (see #1462).
 * Handle case-sensitive file names in the file manager (see #1433).
 * Simplify the slug handling when auto-creating aliases (see #1334).
 * Do not notify the admin via e-mail if an account is locked (see #7728).
 * Only start the session if needed to find data in FORM_DATA (see #1471).
 * Add template blocks to be_login.html5 (see #1424).
 * Improve cache busting of the .css and .js files (see #1404).
 * Add a translator insert tag (see #1400).
 * Distinguish between error 401 and 403 (see #1381).
 * Enable drag and drop for the file manger (see #1394).
 * Add drag and drop file upload for the file tree (see #1386).
