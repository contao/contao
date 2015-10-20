Contao installation bundle change log
=====================================

Version 0.9.9 (2015-XX-XX)
--------------------------

### Fixed
Boot the real system as soon as the `parameters.yml` file exists.

### Fixed
Hide the admin user form if the table does not yet exist (see contao/core-bundle#366).

### Fixed
Set the kernel.cache_dir to a non existing directory (see #3).

### Fixed
Do not try to persist the admin e-mail twice (see contao/core-bundle#344).


Version 0.9.8 (2015-09-08)
--------------------------

### Fixed
Adjust the log file name.


Version 0.9.7 (2015-09-02)
--------------------------

### Fixed
Log into the app/logs/prod.log file.


Version 0.9.6 (2015-08-25)
--------------------------

### Fixed
Register the Contao class loader so third-party config files can be loaded.


Version 0.9.5 (2015-08-14)
--------------------------

### Fixed
Set the correct kernel root directory in the container.


Version 0.9.4 (2015-08-14)
--------------------------

### Fixed
Add the missing form field type update.

### Fixed
Correctly add the `kernel.bundles` parameter (see contao/core-bundle#329).
