<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle;

use Contao\Config;

/**
 * Provides installation related methods.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InstallTool
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * Constructor.
     *
     * @param string $rootDir The root directory
     */
    public function __construct($rootDir)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * Returns true if the install tool has been locked.
     *
     * @return bool True if the install tool has been locked
     */
    public function isLocked()
    {
        return Config::get('installCount') >= 3;
    }

    /**
     * Returns true if the install tool can write files.
     *
     * @return bool True if the install tool can write files
     */
    public function canWriteFiles()
    {
        return is_writable(__FILE__);
    }

    /**
     * Returns true if the license has not been accepted yet.
     *
     * @return bool True if the license has not been accepted yet
     */
    public function shouldAcceptLicense()
    {
        return !Config::get('licenseAccepted');
    }

    /**
     * Creates the local configuration files if they do not yet exist.
     */
    public function createLocalConfigurationFiles()
    {
        if (Config::get('installPassword')) {
            return;
        }

        // The localconfig.php file is created by the Config class
        foreach (['dcaconfig', 'initconfig', 'langconfig'] as $file) {
            if (!file_exists($this->rootDir . '/../system/config/' . $file . '.php')) {
                file_put_contents(
                    $this->rootDir . '/../system/config/' . $file . '.php',
                    '<?php' . "\n\n// Put your custom configuration here\n"
                );
            }
        }
    }
}
