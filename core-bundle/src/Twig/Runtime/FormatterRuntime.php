<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Runtime;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Twig\Extension\RuntimeExtensionInterface;

final class FormatterRuntime implements RuntimeExtensionInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    /**
     * Convert a byte value into a human-readable format.
     */
    public function formatBytes(int $bytes, int $decimals = 1): string
    {
        $this->framework->initialize();

        return System::getReadableSize($bytes, $decimals);
    }
}
