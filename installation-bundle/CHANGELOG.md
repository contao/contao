# Contao installation bundle change log

## 4.5.7 (2018-04-04)

 * Preserve the container directory when purging the cache.

## 4.5.5 (2018-03-06)

 * Support using InnoDB without the `innodb_large_prefix` option.

## 4.5.2 (2018-01-12)

 * Do not parse @@version to determine the database vendor (see #84).

## 4.5.1 (2018-01-04)

 * Check all `innodb_large_prefix` requirements in the install tool (see #80).
 * Use the table options instead of the default table options to compare engine and collation.

## 4.5.0-RC1 (2017-12-12)

 * Check the MySQL version and the configured database options.

## 4.5.0-beta3 (2017-12-04)

 * Also check the table engine and collation during database migration.
