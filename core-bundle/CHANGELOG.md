Contao core bundle change log
=============================

Version 4.0.4 (2015-XX-XX)
--------------------------

### Fixed
Use the kernel.packages to determine the core version (see #351).

### Fixed
Follow symlinks when searching for installed files (see #348).


Version 4.0.3 (2015-09-10)
--------------------------

### Fixed
Strip the `web/` prefix in the `Image::get()` method (see #337).

### Fixed
Update the symlinks after a file or folder has been renamed (see #332).

### Fixed
Correctly trigger the command scheduler in the front end (see #340).

### Fixed
Handle legacy page types not returning a response object (see #331).

### Fixed
Correctly add the bundle style sheets in debug mode (see #328).

### Fixed
Register the related models in the registry (see #333).

### Fixed
Make sure that `TABLE_OPTIONS` is not an array (see #324).

### Fixed
Throw an exception if a module folder does not exist (see #326).

### Fixed
Add the missing `getResponse()` method to the respective page types.

### Fixed
Correctly validate paths in the template editor (see #325).

### Fixed
Correctly handle dimensionless SVG images (see contao/core#7882).

### Fixed
Enable the `strictMath` option of the LESS parser (see contao/core#7985).

### Fixed
Consider the pagination menu when inserting at the top (see contao/core#7895).

### Fixed
Store the correct edit URL in the back end personal data module (see contao/core#7987).

### Fixed
Adjust the breadcrumb trail when creating new folders (see contao/core#7980).

### Fixed
Convert the HTML content to XHTML when generating Atom feeds (see contao/core#7996).

### Fixed
Correctly link the items in the files breadcrumb menu (see contao/core#7965).

### Fixed
Handle explicit collations matching the default collation (see contao/core#7979).

### Fixed
Fix the duplicate content check in the front end controller (see contao/core#7661).

### Fixed
Correctly parse dates in MooTools (see contao/core#7983).

### Fixed
Correctly escape in the `findMultipleFilesByFolder()` method (see contao/core#7966).

### Fixed
Override the tabindex handling of the accordion to ensure that the togglers are
always focusable via keyboard (see contao/core#7963).

### Fixed
Check the script when storing the front end referer (see contao/core#7908).

### Fixed
Fix the back end pagination menu (see contao/core#7956).

### Fixed
Handle option callbacks in the back end help (see contao/core#7951).

### Fixed
Fixed the external links in the text field help wizard (see contao/core#7954) and the
keyboard shortcuts link on the back end start page (see contao/core#7935).

### Fixed
Fixed the CSS group field explanations (see contao/core#7949).

### Fixed
Use ./ instead of an empty href (see contao/core#7967).

### Fixed
Correctly detect Microsoft Edge (see contao/core#7970).

### Fixed
Respect the "order" parameter in the `findMultipleByIds()` method (see contao/core#7940).

### Fixed
Always trigger the "parseDate" hook (see contao/core#4260).

### Fixed
Allow to instantiate the `InsertTags` class (see contao/core#7946).

### Fixed
Do not parse the image `src` attribute to determine the state of an element,
because the image path might have been replaced with a `data:` string (e.g. by
the Apache module "mod_pagespeed").


Version 4.0.2 (2015-08-04)
--------------------------

### Fixed
Make the install tool stand-alone.


Version 4.0.1 (2015-07-24)
--------------------------

### Fixed
Support overwriting the CSS ID in an alias element (see #305).

### Fixed
Add a `StringUtil` class to restore PHP 7 compatibility (see #309).

### Fixed
Correctly handle files in the `/web` directory in the Combiner (see #300).

### Fixed
Fix the argument order of the `ondelete_callback` (see #301).

### Fixed
Correctly apply the class `active` in the pagination template (see #315).

### Fixed
Fix the `Validator::isEmail()` method (see #313).

### Fixed
Strip tags before auto-generating aliases (see contao/core#7857).

### Fixed
Correctly encode the URLs in the popup file manager (see contao/core#7929).

### Fixed
Check for the comments module when compiling the news meta fields (see contao/core#7901).

### Fixed
Also sort the newsletter channels alphabetically in the front end (see contao/core#7864).

### Fixed
Disable responsive images in the back end preview (see contao/core#7875).

### Fixed
Overwrite the request string when generating news/event feeds (see contao/core#7756).

### Fixed
Store the static URLs with the cached file (see contao/core#7914).

### Fixed
Correctly check the subfolders in the `hasAccess()` method (see contao/core#7920).

### Fixed
Updated the countries list (see contao/core#7918).

### Fixed
Respect the `notSortable` flag in the parent (see contao/core#7902).

### Fixed
Round the maximum upload size to an integer value (see contao/core#7880).

### Fixed
Make the markup minification less aggressive (see contao/core#7734).

### Fixed
Filter the indices in `Database::getFieldNames()` (see contao/core#7869).


Version 4.0.0 (2015-06-09)
--------------------------

### Fixed
Fixed several directory separator issues.

### Fixed
Handle bundle images in `Image::get()` (see #287).

### Fixed
Check if a custom folder is protected in the file picker (see #287).

### Fixed
Do not make textareas required if they are replaced with an RTE (see #266).

### Fixed
Correctly show the error messages in the login module (see #269).

### Fixed
Map the referer in the old Session class (see #281).

### Fixed
Store new record IDs in the persistent session bag (see #281).

### Fixed
Correctly reload the page in the install tool (see #267).

### Fixed
Correctly show the color picker images (see #268).

### Fixed
Consolidate the custom sections markup (see contao/core#7843).

### Fixed
Correctly execute the symlinks command in the automator (see #265).

### Fixed
Correctly handle an empty `_locale` attribute (see #262).

### Fixed
Correctly switch between the page and file picker in the hyperlink element.


Version 4.0.0-RC1 (2015-05-15)
------------------------------

### New
Add the "getArticles" hook.

### Fixed
Make `Validator::isValidUrl()` RFC 3986 compliant (see contao/core#7790).

### Changed
Removed the "space before/after" option (see #250).

### Changed
Consolidated the markup of all front end forms (see #249).

### Fixed
Decode sprintf placeholdes passed to `generateFrontendUrl()` as parameters.

### Fixed
Consolidate the templates and module keys (see #247).

### Fixed
Prevent recursion when creating symlinks (see #245).

### Fixed
Append the numeric ID to the `FORM_SUBMIT` variable (see contao/core#7286).

### Changed
Do not render empty custom sections (see contao/core#7742).

### Fixed
Convert dates to timestamps in the form generator (see contao/core#6827).

### New
Add schema.org tags where applicable (see contao/core#7780).

### Fixed
Correctly store the referer URLs (see #143).

### Fixed
Handle the new back end URLs in the JavaScript pickers (see #217).

### Fixed
Do not throw an exception if there are not XLIFF files (see #211).

### Fixed
Correctly check for public folders when loading content via Ajax (see #213).

### Fixed
Replace the old back end paths when generating Ajax responses (see #212).

### New
Added support for specifying the database key length (see #221).

### Fixed
Create absolute symlinks if relative symlinks are not supported (see #208).

### Removed
The "postFlushData" hook has been removed (see #196).

### Fixed
Do not check the database driver in `Config::isComplete()` (see #203).

### Improved
It is now possible to check for an authenticated back end user in a front end
template using `$this->hasAuthenticatedBackendUser()`.


Version 4.0.0-beta1 (2015-04-14)
--------------------------------

### Removed
Removed the `show_help_message()` and `die_nicely()` functions.

### Removed
The `coreOnlyMode` setting has been removed (see #145).

### Removed
The change log viewer has been removed from the back end (see #152).

### Changed
The rich text and code editor configuration files are now real templates, which
can be customized in the template editor.

### Changed
The `debugMode` setting has been removed, since the debug mode is automatically
enabled if the application is called via the `app_dev.php` script.

### Improved
The `rewriteUrl` setting has been removed, because the application now adds or
removes the script fragment automatically.

### Changed
Protect the `DcaExtractor` constructor (use `getInstance()` instead).

### Changed
Return `null` if a widget is empty and the DB field is nullable (see #17).

### Changed
Remove the JS library dependencies from the library agnostic scripts (see #23).

### Changed
Replace the syntax highlighter component with highlight.js.

### Removed
Removed the "default" theme in favor of the "flexible" theme.

### Changed
Load the third-party components via `contao-components`.

### Removed
Removed the MooTools "slimbox" plugin.

### Removed
Removed the CSS3PIE plugin.

### Changed
Make the public extension folders configurable (see #8).

### Fixed
Correctly symlink the upload folder.

### Changed
Do not use a constant for the website path (see contao/core#5347).

### Changed
Support scopes in the `Message` class (see contao/core#6558).

### Changed
Use `<fieldset>` and `<legend>` in the newsletter channel selection menu.

### Changed
Do not auto-generate article IDs from their alias names (see contao/core#4837).

### Fixed
Correctly assign the CSS classes "odd" and "even" to the table element.

### Changed
Use a `<strong>` tag to highlight keywords in search results.

### Changed
Use a `<strong>` tag instead of a `<span>` tag for active menu items.

### Changed
Use the CSS class `active` instead of `current` in the pagination menu.

### Changed
Use the CSS class `previous` instead of `prev` in the book navigation module.

### Fixed
Correctly set the folder protection status when loading subfolders (see #4).

### Changed
Adjust the logic of the `File` class (see contao/core#5341).

### Removed
Remove the Safe Mode Hack, the XHTML resources and the IE6 warning.

### Changed
Move all public resources to the `web/` subdirectory.
