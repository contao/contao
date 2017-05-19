# Contao installation bundle change log

### DEV

 * Ignore tables not starting with "tl_" in the install tool (see #51).
 * Re-add the "sqlCompileCommand" hook (see #51).
 * Purge the opcode caches after deleting the Symfony cache (see contao/contao-manager#80).
