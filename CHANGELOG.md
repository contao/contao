# Change log

## DEV

 * Invalidate the user sessions if a password changes (see CVE-2019-10641).
 * Correctly check if a file or folder is excluded from synchronization (see 410).

## 4.7.2 (2019-03-25)

 * Make custom layout section titles and IDs mandatory (see #341).
 * Always add the host to the title attribute in the back end (see #339).
 * Prevent using reserved layout section IDs in custom layout sections (see #301).
 * Show the video elements headline in the back end preview (see #382).
 * Correctly check for related records when purgin opt-in tokens.

## 4.7.1 (2019-02-21)

 * Do not add `crossorigin="anonymous"` to all external scripts (see #356).
 * Use the host as title on the login page (see #339).
 * Correctly set the root IDs in the "checkPermission" callbacks (see #320).
 * Fix the format selection in the image size widget (see #315).
 * Ignore a `.public` file in the root files directory (see #286).  
 * Correctly load MooTools via CDN (see #318).
 * Hide the impersonation link if already in impersonation mode (see #345).
 * Store the user object after it has been loaded (see #347).
 * Do not double decode URL fragments (see #321).
 * Correctly replace insert tags if the page contains invalid characters (see #349).
 * Correctly redirect to the preferred language for empty URL (see #336).

## 4.7.0 (2019-02-15)

 * Fix the input field height on Windows (see #79).

## 4.7.0-RC4 (2019-02-08)

 * Correctly match root pages with hostname and port (see #306).
 * Fix the "Recreate the symlinks" maintenance task (see #299).

## 4.7.0-RC3 (2019-01-24)

 * Add the `js_nocookie.html5` template (see #134).
 * Correctly cancel the 2FA process (see #292).
 * Check the database configuration the install tool (see #285).
 * Validate the primary key when registering or saving a model (see #230).
 * Exempt the "page" insert tag from caching (see #284).
 * Correctly sort the tree view records if there is an active filter (see #269).
 * Fix two routing issues (see #263, #264). 

## 4.7.0-RC2 (2019-01-17)

 * Support comma separated values in `Model::getRelated()` (see #257).
 * Do not check the user's file permissions in the template editor (see #224).
 * Do not show pretty errors if "text/html" is not accepted (see #249).
 * Return `null` in `Model::findMultipleByIds()` if there are no models (see #266).
 * Restore compatibility with Doctrine DBAL 2.9 (see #256).

## 4.7.0-RC1 (2019-01-15)

 * Warn if there are user groups granting access to the template editor (see #224).
 * Use the Symfony CMF router (see #95).
 * Increase the back end preview image dimensions (see #246).
 * Add the "contao.slug" service (see #222).
 * Add the "contao.opt-in" service (see #196).
 * Add the onshow_callback (see #235).
 * Enable drag and drop for templates (see #223).
 * Add the integrity attribute when loading jQuery from CDN (see contao/core-bundle#702).
 * Add methods to retrive past and upcoming dates in the event reader (see #175).
 * Associate comments with members (see contao/comments-bundle#7).
 * Add content disposition to download elements (see #20).
 * Move the "syncExclude" option to the file manager (see #203).
 * Move the "minifyHtml" option to the page layout (see #203).
 * Use a native font stack instead of a web font in the back end (see #98).
 * Improve the text for repeated events (see #175).
 * Allow to overwrite the page title and description in news and events (see #161).
 * Show all root languages by default in the meta editor (see contao/core#6254).
 * Try to preserve existing .htaccess entries when installing the web directory (see #160).
 * Use the alternative text from the image meta data if none is given (see #165).
 * Improve the API for protecting files and folders (see contao/core-bundle#1601).
 * Move the TCPDF export into a separate bundle (see #65).
 * Stop using kernel.root_dir (forward compatibility with Symfony 4.2).
 * Add support for routes in DCA operations (see #116).
 * Add a timestamp to all calendar cells (see #47).
 * Allow to tag services as data container callback (see #39).
 * Show a text key to set up 2FA in case the QR code cannot be scanned (see #86).
 * Show root pages in the custom navigation module (see contao/core-bundle#1641).
 * Add the `removeField()` method to the palette manipulator (see contao/core-bundle#1668).
 * Allow to select a news reader in the news list (see contao/news-bundle#39).
 * Show the current page title and URL in the preview bar (see contao/core-bundle#1640).
