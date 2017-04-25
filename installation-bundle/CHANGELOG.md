# Contao installation bundle change log

### 4.3.9 (2017-04-25)
 
 * Add the contao_installation.initialize_application event (fixes contao/manager-bundle#19).

### 4.3.6 (2017-03-22)

 * Correctly parse column definitions with comma (see #47).

### 4.3.5 (2017-02-14)

 * Fix the path to the log file (see #41).

### 1.2.4 (2016-01-23)

 * Fix the version contraints of the manager plugin.

### 1.2.3 (2017-01-18)

 * Require version 0.2 of the manager plugin.

### 1.2.2 (2017-01-05)

 * Fix the database.html.twig template (see #39).
 * Also create a kernel secret if there is no secret at all (see #37).

### 1.2.1 (2016-12-21)

 * Do not restrict the database name characters (see contao/core-bundle#593).
 * Do not check for the bootstrap.php.cache file (see #47).

### 1.2.0 (2016-11-25)

 * Use the Filesystem to dump the parameters.yml file.

### 1.2.0-RC1 (2016-10-31)

 * Show the console output if a post installation task cannot be completed (see #32).
 * Make the legacy configuration files optional (see contao/core-bundle#521).
