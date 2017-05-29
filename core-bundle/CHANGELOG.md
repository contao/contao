# Contao core bundle change log

### DEV

 * Correctly set the important part in "edit multiple" mode (see #839).
 * Remove the broken alias transliteration see (#848).

### 4.4.0-RC1 (2017-05-23)

 * Tweak the back end template.
 * Add the "allowed member groups" setting (see contao/core#8528).
 * Hide the CAPTCHA field by default by adding a honeypot field (see #832).
 * Add the "href" parameter to the article list (see #694).
 * Show both paths and UUIDs in the "show" view (see #793).
 * Do not romanize file names anymore.
 * Improve the back end breadcrumb menu (see #623).
 * Correctly render the image thumbnails (see #817).
 * Use the "file" insert tag in the file picker where applicable (see contao/core#8578).
 * Update the Punycode library to version 2 (see #748).
 * Always add custom meta fields to the templates (see #717).

### 4.4.0-beta1 (2017-05-05)

 * Warn if another user has edited a record when saving it (see #809).
 * Optimize the element preview height (see #810).
 * Use the file meta data by default when adding an image (see #807).
 * Use the kernel.project_dir parameter (see #758).
 * Disable the "publish" checkbox if a parent folder is public (see #712).
 * Improve the findByIdOrAlias() method (see #729).
 * Make sure that all modules can have a custom template (see #704).
 * Remove the popupWidth and popupHeight parameters in the file manager (see #727).
 * Remove the cron.txt file (see #753).
 * Allow to disable input encoding for a whole DCA (see #708).
 * Add the DCA picker (see #755).
 * Allow to manually pass a value to any widget (see #674).
 * Only prefix an all numeric alias when standardizing (see #707).
 * Support using objects in callback arrays (see #699).
 * Support importing form field options from a CSV file (see #444).
 * Add a Doctrine DBAL field type for UUIDs (see #415).
 * Support custom backend routes (see #512).
 * Add the contao.image.target_dir parameter (see #684).
 * Match the security firewall based on the request scope (see #677).
 * Add the contao.web_dir parameter (see contao/installation-bundle#40).
 * Look up the form class and allow to choose the type (see contao/core#8527).
 * Auto-select the active page in the quick navigation/link module (see contao/core#8587).
