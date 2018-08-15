# Contao installation bundle change log

## 4.4.21 (2018-08-13)

 * Fix the MySQL 8 compatibility (see #93).

## 4.4.17 (2018-04-04)

 * Preserve the container directory when purging the cache.

## 4.4.14 (2018-02-14)

 * Log database connection errors (see contao/core-bundle#1324).

## 4.4.12 (2018-01-03)

 * The assets:install command requires the application to be set in Symfony 3.4 (see #81).

## 4.4.10 (2017-12-27)

 * Use the schema filter in the install tool (see #78).

## 4.4.9 (2017-12-14)

 * Use a simpler lock mechanism in the install tool (see #73).

## 4.4.3 (2017-08-16)

 * Warm up the Symfony cache after the database credentials have been set (see #63).
 * Check if the Contao framework has been initialized when adding the user agent string (see standard-edition#64).

## 4.4.1 (2017-07-12)

 * Correctly set the "overwriteMeta" field during the database update (see contao/core-bundle#888).

## 4.4.0-RC1 (2017-05-23)

 * Ignore tables not starting with "tl_" in the install tool (see #51).
 * Re-add the "sqlCompileCommand" hook (see #51).
 * Purge the opcode caches after deleting the Symfony cache (see contao/contao-manager#80).
