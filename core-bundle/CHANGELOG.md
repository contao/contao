# Contao core bundle change log

### DEV
 
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
