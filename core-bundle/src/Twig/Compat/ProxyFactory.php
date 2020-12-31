<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Compat;

class ProxyFactory
{
    /**
     * @return mixed
     */
    public static function createValueHolder($value, string $name = '')
    {
        if (!self::needsWrapping($value)) {
            return $value;
        }

        if (\is_callable($value)) {
            return new InvokableValueHolder($value, $name);
        }

        if (\is_object($value)) {
            return new ObjectValueHolder($value, $name);
        }

        if (\is_array($value)) {
            return new ArrayValueHolder($value, $name);
        }

        return new ScalarValueHolder($value, $name);
    }

    private static function needsWrapping($value): bool
    {
        $type = \gettype($value);

        // Types that never need to be escaped
        if (\in_array($type, ['boolean', 'integer', 'double', 'NULL'], true)) {
            return false;
        }

        // Numeric strings
        if ('string' === $type && is_numeric($value)) {
            return false;
        }

        // Objects that are explicitly marked
        if ($value instanceof SafeHTMLValueHolderInterface) {
            return false;
        }

        // Empty strings/arrays
        return !empty($value);

        // A more sophisticated check could go here in the future that further
        // limits the amount of proxy objects that need to be created.
    }
}
