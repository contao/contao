# Contao installation bundle change log

### DEV

 * Check if the Contao framework has been initialized when adding the user agent string (see standard-edition#64).

### 4.4.1 (2017-07-12)

 * Correctly set the "overwriteMeta" field during the database update (see contao/core-bundle#888).

### 4.4.0-RC1 (2017-05-23)

 * Ignore tables not starting with "tl_" in the install tool (see #51).
 * Re-add the "sqlCompileCommand" hook (see #51).
 * Purge the opcode caches after deleting the Symfony cache (see contao/contao-manager#80).
