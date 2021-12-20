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
    private array $keepDays;

    public function __construct(int $keepMax, array $keepDays = [])
    {
        $this->keepMax = $keepMax;
        $this->keepDays = $this->validateAndSortKeepDays($keepDays);
    }

    public function getKeepMax(): int
    {
        return $this->keepMax;
    }

    public function getKeepDays(): array
    {
        return $this->keepDays;
    }

    private function validateAndSortKeepDays(array $keepDays): array
    {
        foreach ($keepDays as $numberOfDays) {
            if (!\is_int($numberOfDays)) {
                throw new \InvalidArgumentException('$keepDays must be an array of integers.');
            }
        }

        sort($keepDays);

        return $keepDays;
    }
}
