<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

/**
 * @internal
 */
class GlobalsMapListener
{
    public function __construct(private array $globals)
    {
    }

    /**
     * Maps fragments to the globals array.
     */
    public function onInitializeSystem(): void
    {
        foreach ($this->globals as $key => $value) {
            if (\is_array($value) && isset($GLOBALS[$key]) && \is_array($GLOBALS[$key])) {
                $GLOBALS[$key] = array_replace_recursive($GLOBALS[$key], $value);
            } else {
                $GLOBALS[$key] = $value;
            }
        }
    }
}
