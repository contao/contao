<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test;

/**
 * # FIXME
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    protected static $warningEnabledOrig       = null;
    protected static $noticeEnabledOrig        = null;
    protected static $errorReportingOrig       = null;
    protected static $isDisabledErrorReporting = false;

    /**
     * Tears down the fixture.
     *
     * This method is called after a test is executed.
     */
    final protected function tearDown()
    {
        if (true === static::$isDisabledErrorReporting) {
            $this->enableErrorReporting();
        }
    }

    /**
     * Disables error reporting.
     *
     * @see http://stackoverflow.com/questions/1225776/test-the-return-value-of-a-method-that-triggers-an-error-with-phpunit
     */
    public function disableErrorReporting()
    {
        static::$warningEnabledOrig = \PHPUnit_Framework_Error_Warning::$enabled;
        static::$noticeEnabledOrig  = \PHPUnit_Framework_Error_Notice::$enabled;
        static::$errorReportingOrig = error_reporting();

        \PHPUnit_Framework_Error_Warning::$enabled = false;
        \PHPUnit_Framework_Error_Notice::$enabled  = false;

        error_reporting(0);

        static::$isDisabledErrorReporting = true;
    }

    /**
     * Enables error reporting.
     */
    public function enableErrorReporting()
    {
        \PHPUnit_Framework_Error_Warning::$enabled = static::$warningEnabledOrig;
        \PHPUnit_Framework_Error_Notice::$enabled  = static::$noticeEnabledOrig;

        error_reporting(static::$errorReportingOrig);

        static::$isDisabledErrorReporting = false;
    }
}
