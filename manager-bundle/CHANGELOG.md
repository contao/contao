# Contao manager bundle change log

## DEV

 * Revert the intermediate maintenance mode fix (see #78)

## 4.4.19 (2018-06-18)

 * Disable the maintenance mode for local requests (see contao/core-bundle#1492).

## 4.4.17 (2018-04-04)

 * Suppress error messages in production (see contao/core-bundle#1422).

## 4.4.14 (2018-02-14)

 * Remove "allow_reload" in favor of the "expect" header (see terminal42/header-replay-bundle#11).

## 4.4.5 (2017-09-18)

 * Catch the DriverException if the database connection fails (see contao/managed-edition#27).

## 4.4.0-beta1 (2017-05-05)

 * Extended reporting if the script handler fails (see #27).
 * Set prepend_locale to false (see contao/core-bundle#785).
 * Change the maintenance lock file path (see contao/core-bundle#728).
 * Add basic security (see contao/standard-edition#54).
