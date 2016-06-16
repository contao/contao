# Contao core bundle change log

### 4.2.0 (2016-06-XX)

 * Always use the relative path in DC_Folder (see contao/core#8370).
 * Handle the `useAutoItem` option in the URL generator (see #489).
 * Correctly implement the "autoplay" option in the YouTube/Vimeo element (see #509).
 * Show the system messages in a modal dialog (see #486).
 * Make DropZone the default uploader in the file manager (see #504).
 * Correctly aling the input hints (see #503).
 * Disable the "preview as" button if a member is not allowed to log in (see #502).
 * Remove the "type" attribute from the Youtube/Vimeo iframe tag.
 * Fix the filter reset buttons (see #496).
 * Fix the filename case of the new SVG icons (see #492).

### 4.2.0-RC1 (2016-05-18)

 * Add the URI when throwing 403 and 404 exceptions (see #369).
 * Correctly determine the script_name in the Environment class (see #426).
 * Add the URL generator (see #480).
 * Support subpalettes in subpalettes (see #450).
 * Add keys to the cronjobs array (see #440).

### 4.2.0-beta1 (2016-04-25)

 * Remove the internal cache routines from the maintenance module (see #459).
 * Modernize the back end theme and use SVG icons (see contao/core#4608).
 * Add the PaletteManipulator class to modify DCA palettes (see #474).
 * Optimize the jQuery and MooTools templates (see contao/core#8017).
 * Show the record ID and table name in the diff view (see contao/core#5800).
 * Add a "reset filters" button (see contao/core#6239).
 * Support filters in the tree view (see contao/core#7074).
 * Recursively replace insert tags (see #473).
 * Add the Vimeo content element (see contao/core#8219).
 * Improve the YouTube element (see contao/core#7514).
 * Update the Piwik tracking code (see contao/core#8229).
 * Use instanceof to check the return value of Model::getRelated() (see #451).
 * Use the Composer class loader to load the Contao classes (see #437).
 * Add the "ignoreFePreview" flag to the model classes (see #452).
 * Clean up the code of the file selector (see #456).
