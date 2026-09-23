<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Widget;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Date;

final class DateValueFormatter
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function format(mixed $value, string $rgxp): string|null
    {
        if ('' === $value || !\in_array($rgxp, ['date', 'time', 'datim'], true)) {
            return null;
        }

        $this->framework->initialize();

        try {
            // Resolve the current page and configuration formats at call time through the
            // legacy Date implementation
            $format = $this->framework->getAdapter(Date::class)->getFormatFromRgxp($rgxp);
            $date = $this->framework->createInstance(Date::class, [$value, $format]);

            return $date->$rgxp;
        } catch (\OutOfBoundsException) {
            // Ignore if date could not be converted
            return null;
        }
    }
}
