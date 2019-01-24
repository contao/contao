# Change log

## DEV

 * Check the database configuration the install tool (see #285).
 * Validate the primary key when registering or saving a model (see #230).
 * Exempt the "page" insert tag from caching (see #284).
 * Correctly sort the tree view records if there is an active filter (see #269).
 * Fix two routing issues (see #263, #264). 

## 4.6.13 (2019-01-16)

 * Support comma separated values in `Model::getRelated()` (see #257).
 * Do not check the user's file permissions in the template editor (see #224).
 * Do not show pretty errors if "text/html" is not accepted (see #249).
 * Return `null` in `Model::findMultipleByIds()` if there are no models (see #266).
 * Restore compatibility with Doctrine DBAL 2.9 (see #256).

## 4.6.12 (2018-12-19)

 * Fix another compatibility issue with Doctrine DBAL 2.9 (see #238).
 * Correctly check the permission to move child records as non-admin user (see #247).
 * Do not parse form templates twice (see #214).

## 4.6.11 (2018-12-13)

 * Prevent information disclosure through incorrect access control in the back end (see CVE-2018-20028).

## 4.6.10 (2018-12-04)

 * Do not add a favicon.ico file by default (see #181).
 * Fix a compatibility issue with Doctrine DBAL 2.9 (see #212).

## 4.6.9 (2018-11-22)

 * Fix a BC break in the FileUpload class (see #188).
 * Do not convert line breaks in table cells if there are HTML block elements (see #159).
 * Automatically enable image sizes created by regular users (see contao/core#8836).
 * Handle unknown languages in the meta editor (see #127).

## 4.6.8 (2018-11-12)

 * Correctly redirect in the "close account" module (see #93).
 * Correctly rebuild the symlinks in the maintenance module (see #150).

## 4.6.7 (2018-10-31)

 * Correctly rebuild the symlinks in the maintenance module (see #150).
 * Hide members without login in the "rebuild search index" drop-down (see #91).
 * Check the member status when sending newsletters (see contao/core#8812).
 * Fix the schema.org markup of the breadcrumb menu (see contao/core-bundle#1561).
 * Allow to set the target directory when installing the web directory (see #142).
 * Correctly render the back end forms in Firefox (see #79).
 * Show the info messages in the DropZone uploader (see #83).

## 4.6.6 (2018-10-01)

 * Drop indexes when changing engine or collation (see #106).
 * Also check innodb_file_per_table in the DCA schema provider (see #103).

## 4.6.5 (2018-09-20)

 * Fix an error when creating new pages (see #63).

## 4.6.4 (2018-09-18)

 * Check wether the database supports dynamic rows in the install tool (see #59).
 * Correctly detect Chrome on iOS in the environment class (see #61).
 * Optimize generating sitemaps (see contao/core#6830).
 * Use min-height for .w50 widgets in the back end (see contao/core#8864).
 * Prevent arbitrary code execution through .phar files (see CVE-2018-17057).

## 4.6.3 (2018-09-05)

 * Make the `contao.controller.backend_csv_import` controller public (see #49).
 * Re-add the installation bundle commands (see #45).
 * Also check the row format when updating an InnoDB table.
 * Allow SwiftMailer 5 and 6 (see contao/core-bundle#1613).
 * Also add the `youtubeStart` and `youtubeStop` fields in the version 4.5 update (see #28).
 * Ignore the `uncached` and `refresh` insert tag flags in the unknown insert tags (see #48).
 * Make the ID of the subscription modules unique (see #40).
 * Use the correct table when handling root nodes in the picker (see #44).

## 4.6.2 (2018-08-28)

 * Replace the `Set-Cookie` header when merging HTTP headers (see #35).

## 4.6.1 (2018-08-28)

 * Restore compatibility with `symfony/http-kernel` in version 3.4 (see #34).
 * Do not merge the session cookie header (see #11, #29).
 * Update the list of countries (see #12).
 * Add the `tl_content.youtubeOptions` field in the version 4.5 update (see #28).

## 4.6.0-RC1 (2018-07-16)

 * Add two factor authentication for the back end login (see contao/core-bundle#1545).
 * Add the "markAsCopy" config option to the DCA (see contao/core-bundle#586).
 * Sort the custom layout sections by their position (see contao/core-bundle#1529).
 * Make the DropZone error messages translatable (see contao/core-bundle#1320).
 * Do not stack the buttons if the screen is wide enough (see contao/core#8816).
 * Remove registrations that are not activated within 24 hours (see contao/core-bundle#1512).
 * Remove the "number of columns" field from the login module (see contao/core-bundle#1577).
 * Also use the file meta data in the download element (see contao/core-bundle#1459).
 * Purge the system log by default after 7 days (see contao/core-bundle#1512).
 * Use "noreferrer noopener" for links that open in a new window (see contao/core-bundle#1125).
 * Do not store IP addresses in tl_log (see contao/core-bundle#1512).
 * Append the module ID to the form field IDs to prevent duplicate IDs (see contao/core-bundle#1493).
 * Add the page picker to the meta fields in file manager (see contao/core-bundle#1568).
 * Remove the "flash movie" front end module.
 * Add extended video support (see contao/core-bundle#1348).
 * Pass all search result data to the search template (see contao/core-bundle#1558).
 * Auto-clear the session form data (see contao/core-bundle#1550).
 * Add abstract controllers for fragment content elements and front end modules (see contao/core-bundle#1376).
 * Handle manifest.json files in public bundle folders (see contao/core-bundle#1510).
 * Introduce auto cache tag invalidation on DCAs for better DX (see contao/core-bundle#1478).
 * Throw a "page not found" exception if a download file does not exist (see contao/core#8375).
 * Allow multi-root and multi-page searches (see contao/core-bundle#1462).
 * Handle case-sensitive file names in the file manager (see contao/core-bundle#1433).
 * Simplify the slug handling when auto-creating aliases (see contao/core-bundle#1334).
 * Do not notify the admin via e-mail if an account is locked (see contao/core-bundle#7728).
 * Only start the session if needed to find data in FORM_DATA (see contao/core-bundle#1471).
 * Add template blocks to be_login.html5 (see contao/core-bundle#1424).
 * Improve cache busting of the .css and .js files (see contao/core-bundle#1404).
 * Add a translator insert tag (see contao/core-bundle#1400).
 * Distinguish between error 401 and 403 (see contao/core-bundle#1381).
 * Enable drag and drop for the file manger (see contao/core-bundle#1394).
 * Add drag and drop file upload for the file tree (see contao/core-bundle#1386).
 * Hide the end time of open-ended events in the back end form (see contao/calendar-bundle#23).
 * Link to the event if no image link has been defined (see contao/news-bundle#30).
 * Hide running non-recurring events (see contao/calendar-bundle#30).
 * Pass the number of events to the main template (see contao/calendar-bundle#32).
 * Make the calendar model available in the event reader (see contao/core#8869).
 * Remove subscriptions that are not activated within 24 hours (see contao/core-bundle#1512).
 * Append the parent ID to the form field IDs to prevent duplicate IDs (see contao/core-bundle#1493).
 * Replace `<div class="quote">` with `<blockquote>` (see contao/core#2244).
 * Support sorting enclosures (see contao/calendar-bundle#16).
 * Migrate the search module settings (see contao/installation-bundle#89).
 * Enable the FOS cache bundle (see contao/manager-bundle#67).
 * Add a default robots.txt file (see contao/manager-bundle#56).
 * Link to the news article if no image link has been defined (see contao/news-bundle#30).
 * Show the 404 page if an external news is opened in the news reader (see contao/news-bundle#33).
 * Add a custom text field to the subscribe module (see contao/core-bundle#1512).
 * Use type="email" in the default template (see contao/newsletter-bundle#20).
