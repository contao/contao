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
    public function __construct(private readonly array $globals)
    {
    }

    /**
     * Maps fragments to the globals array.
     */
    public function onInitializeSystem(): void
    {
        foreach ($this->globals as $key => $priorities) {
            if (isset($GLOBALS[$key]) && \is_array($GLOBALS[$key])) {
                if (isset($priorities[0])) {
                    $priorities[0] = array_replace_recursive($priorities[0], $GLOBALS[$key]);
                } else {
                    $priorities[0] = $GLOBALS[$key];
                }
            }

            ksort($priorities);

            $GLOBALS[$key] = array_replace_recursive($GLOBALS[$key] ?? [], ...array_values($priorities));
        }
    }
}
