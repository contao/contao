<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\Backup\Config;

class RetentionPolicy
{
    private int $keepMax;
    private array $keepPeriods;

    public function __construct(int $keepMax, array $keepPeriods = [])
    {
        $this->keepMax = $keepMax;
        $this->keepPeriods = $this->validateAndSortKeepPeriods($keepPeriods);
    }

    public function getKeepMax(): int
    {
        return $this->keepMax;
    }

    public function getKeepPeriods(): array
    {
        return $this->keepPeriods;
    }

    private function validateAndSortKeepPeriods(array $keepPeriods): array
    {
        foreach ($keepPeriods as $numberOfDays) {
            if (!\is_int($numberOfDays)) {
                throw new \InvalidArgumentException('$keepPeriods must be an array of integers.');
            }
        }

        sort($keepPeriods);

        return $keepPeriods;
    }
}
