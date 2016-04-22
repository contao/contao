# Contao news bundle change log

### 4.1.3 (2016-04-22)

 * Adjust the phpDoc return types of the models.

### 4.1.2 (2016-03-22)

 * Respect the SSL settings of the root page when generating sitemaps (see contao/core#8270).

### 4.1.1 (2016-03-03)

 * Always fix the domain and language when generating URLs (see contao/core#8238).

### 4.1.0 (2015-11-26)

 * Subscribe to the events to modify the front end preview URL.
 * Correctly set the ID when toggling fields via Ajax (see contao/core#8043).

### 4.1.0-RC1 (2015-10-11)

 * Throw an exception instead of redirecting to `/contao?act=error` (see contao/core-bundle#395).
 * Adjust the code to be compatible with PHP7 (see contao/core#8018).
 * Add the `News::generateNewsUrl()` method (see contao/core#7504).

### 4.1.0-beta1 (2015-10-21)

 * Add all translations which are at least 95% complete.
