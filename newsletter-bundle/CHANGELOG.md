# Contao newsletter bundle change log

### 4.1.1 (2016-03-03)

 * Always fix the domain and language when generating URLs (see contao/core#8238).

### 4.1.0 (2015-11-26)

 * Correctly set the ID when toggling fields via Ajax (see contao/core#8043).

### 4.1.0-RC1 (2015-11-11)

 * Prevent adding or importing unsubscribed e-mail addresses (see contao/core#4999).
 * Require to add a sender address in the channel settings (see contao/core#2896).
 * Throw an exception instead of redirecting to `/contao?act=error` (see contao/core-bundle#395).
 * Adjust the code to be compatible with PHP7 (see contao/core#8018).

### 4.1.0-beta1 (2015-10-21)

 * Add all translations which are at least 95% complete.
 * Add a CAPTCHA to the newsletter subscription modules (see contao/core#7402).
