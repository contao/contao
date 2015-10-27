# Contao core bundle change log

### 4.1.0-RC1 (2015-XX-XX)

 * Handle an empty input in the meta wizard (see #382).
 * Use the `kernel.terminate` event for the command scheduler (see #244).
 * Support news and event links in the front end preview (see contao/core#7504).

### 4.1.0-beta1 (2015-10-21)

 * Add all translations which are at least 95% complete.
 * Use the Lexik maintenance bundle to put the app into maintenance mode (see #283).
 * Update the hash of an existing file in `Dbafs::addResource()` (see contao/core#7828).
 * Add a more accurate e-mail validation with unicode support (see #367).
 * Add the "env::base_url" insert tag (see #322).
 * Make the file manager and file picker searchable (see contao/core#7196).
 * Show the website title in the back end (see contao/core#7840).
 * Re-send the activation mail upon a second registration attempt (see contao/core#7992).
 * Always show the "save and edit" button (see contao/core#3567).
 * Highlight the rows on hover via CSS (see contao/core#7837).
 * Show the important part in the file tree (see contao/core#7865).
 * Also show the field names in "edit multiple" mode (see contao/core#7868).
 * Add a button to remove single images from the file tree selection (see contao/core#6684).
 * Support overwriting resources in `app/Resources/contao` (see #314).
