# Contao core bundle change log

### 4.3.0 (2016-11-25)

 * Do not cache pages if a back end user is logged in (see #628).
 * Correctly show error messages in the login module (see #610).
 * Do not hardcode paths in the contao:install and contao:symlinks commands.
 * Store absolute paths in the template loader cache (see #607).

### 4.3.0-RC1 (2016-10-31)

 * Set the secure cookie flag when using SSL (see contao/core#8474).
 * Allow to select a custom form template (see contao/core#8454).
 * Do not show protected elements if "show unpublished elements" is enabled (see contao/core#8149).
 * Scroll form error messages into view (see contao/core#8044).
 * Add a "title" field to the meta wizard (see #601).
 * Support using namespaces and use statements in DCA and config files (see contao/core#8530).
 * Make the back end navigation keyboard navigable (see contao/core#8526).
 * Correctly set host and scheme in the URL generator (see #592).
 * Add the "save and duplicate" button (see contao/core#8510).
 * Add more flexible custom layout sections (see contao/core#6630).
 * Make all wizards sortable via keyboard (see #583).
 * Add CSS classes to image sizes (see #555).
 * Improve the responsiveness of the back end theme.
 * Provide an image service to handle image and picture elements (see #342).
 * Allow to adjust the Contao configuration by adding settings under "contao.localconfig" (see #521).
 * Make script combination optional in case the website supports HTTP/2 (see #484).
 * Automatically find the template files.
 * Make the legacy configuration files optional (see #521).
 * Move the analytics templates to the head section (see contao/core#8182).
