<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Util;

class DeprecationHelper
{
    /**
     * Triggers a deprecation notice only if its not called from within the
     * Contao namespace.
     *
     * @param string $package The name of the Composer package that is triggering the deprecation
     * @param string $version The version of the package that introduced the deprecation
     * @param string $message The message of the deprecation
     * @param mixed  ...$args Values to insert in the message using printf() formatting
     */
    public static function triggerIfCalledFromOutside(string $package, string $version, string $message, mixed ...$args): void
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);

        if (isset($backtrace[0]['class']) && str_starts_with($backtrace[0]['class'], 'Contao\\')) {
            return;
        }

        trigger_deprecation($package, $version, $message, $args);
    }
}
