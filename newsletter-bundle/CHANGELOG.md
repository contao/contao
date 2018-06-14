# Contao newsletter bundle change log

## DEV

 * Use a given e-mail address in the unsubscribe module (see #12).
 * Delete old subscriptions if the new e-mail address exists (see #19).

## 4.4.17 (2018-04-04)

 * Correctly duplicate recipients if a channel is duplicated (see #15).

## 4.4.13 (2018-01-23)

 * Do not remove old subscriptions not related to the selected channels (see contao/core#8824).

## 4.4.5 (2017-09-18)

 * Check if the session has been started before using the flash bag.

## 4.4.4 (2017-09-05)

 * Correctly read the newsletter channel target page in the newsletter list (see #7).

## 4.4.0-RC2 (2017-06-12)

 * Trigger all the callbacks in the toggleVisibility() methods (see contao/core-bundle#756).

## 4.4.0-beta1 (2017-05-05)

 * Optimize the element preview height (see contao/core-bundle#678).
 * Improve the findByIdOrAlias() method (see contao/core-bundle#729).
