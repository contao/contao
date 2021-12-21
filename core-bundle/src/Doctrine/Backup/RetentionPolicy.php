<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\Backup;

class RetentionPolicy implements RetentionPolicyInterface
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

    public function apply(Backup $latestBackup, array $allBackups): array
    {
        $toKeep = $allBackups;
        $keepMax = $this->getKeepMax();
        $keepPeriods = $this->getKeepPeriods();

        // Cleanup according to days retention policy first
        if (0 !== \count($keepPeriods)) {
            $latestDateTime = $latestBackup->getCreatedAt();
            $assignedPerPeriod = array_fill_keys($keepPeriods, null);

            foreach ($allBackups as $k => $backup) {
                // Do not assign the latest
                if (0 === $k) {
                    continue;
                }

                foreach (array_keys($assignedPerPeriod) as $period) {
                    $diffDays = (int) $latestDateTime->diff($backup->getCreatedAt())->format('%a');

                    if ($diffDays <= $period) {
                        $assignedPerPeriod[$period] = $backup;
                    }
                }
            }

            // Always keep the latest
            $toKeep = array_merge([$latestBackup], array_filter($assignedPerPeriod));

            // Ensure sorting again
            usort($toKeep, static fn (Backup $a, Backup $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
        }

        // Then cleanup according to maximum amount of backups to keep
        if ($keepMax > 0) {
            $toKeep = \array_slice($toKeep, 0, $keepMax);
        }

        return $toKeep;
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
