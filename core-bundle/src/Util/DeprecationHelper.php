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
     * Triggers a deprecation if called from outside the Contao namespace.
     *
     * @param string $package The name of the Composer package that triggers the deprecation
     * @param string $version The version of the package that introduced the deprecation
     * @param string $message The message of the deprecation
     * @param mixed  ...$args Values to insert in the message using printf() formatting
     */
    public static function triggerIfCalledFromOutside(string $package, string $version, string $message, mixed ...$args): void
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        if (str_starts_with($backtrace[2]['class'] ?? '', 'Contao\\')) {
            return;
        }

        trigger_deprecation($package, $version, $message, ...$args);
    }

    /**
     * Triggers a deprecation if called on an extended object outside the Contao namespace.
     *
     * @param string $package The name of the Composer package that triggers the deprecation
     * @param string $version The version of the package that introduced the deprecation
     * @param string $message The message of the deprecation
     * @param mixed  ...$args Values to insert in the message using printf() formatting
     */
    public static function triggerIfObjectFromOutside(string $package, string $version, string $message, mixed ...$args): void
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        if (isset($backtrace[1]['object']) && str_starts_with($backtrace[1]['object']::class, 'Contao\\')) {
            return;
        }

        trigger_deprecation($package, $version, $message, ...$args);
    }
}
