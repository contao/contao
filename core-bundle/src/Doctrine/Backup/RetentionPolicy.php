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

final class RetentionPolicy implements RetentionPolicyInterface
{
    private int $keepMax;

    /**
     * @var array<string, \DateInterval>
     */
    private array $keepIntervals;

    public function __construct(int $keepMax, array $keepIntervals = [])
    {
        $this->keepMax = $keepMax;
        $this->keepIntervals = $this->createKeepIntervals($keepIntervals);
    }

    public function apply(Backup $latestBackup, array $allBackups): array
    {
        $toKeep = $allBackups;

        // Cleanup according to days retention policy first
        if (0 !== \count($this->keepIntervals)) {
            $latestDateTime = $latestBackup->getCreatedAt();
            $assignedPerInterval = array_fill_keys(array_keys($this->keepIntervals), null);

            foreach ($allBackups as $k => $backup) {
                // Do not assign the latest
                if (0 === $k) {
                    continue;
                }

                foreach (array_keys($assignedPerInterval) as $intervalReadable) {
                    $interval = $this->keepIntervals[$intervalReadable];

                    if (-1 === $this->compareDateIntervals($latestDateTime->diff($backup->getCreatedAt(), true), $interval)) {
                        $assignedPerInterval[$intervalReadable] = $backup;
                    }
                }
            }

            // Always keep the latest
            $toKeep = array_merge([$latestBackup], array_filter($assignedPerInterval));

            // Ensure sorting again
            usort($toKeep, static fn (Backup $a, Backup $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
        }

        // Then cleanup according to maximum amount of backups to keep
        if ($this->keepMax > 0) {
            $toKeep = \array_slice($toKeep, 0, $this->keepMax);
        }

        return $toKeep;
    }

    /**
     * @throws \Exception
     */
    private function createKeepIntervals(array $keepIntervals): array
    {
        $intervalsNew = [];

        foreach ($keepIntervals as $interval) {
            $intervalsNew[$interval] = new \DateInterval('P'.$interval);
        }

        uasort($intervalsNew, fn (\DateInterval $a, \DateInterval $b) => $this->compareDateIntervals($a, $b));

        return $intervalsNew;
    }

    private function compareDateIntervals(\DateInterval $a, \DateInterval $b): int
    {
        $ref = new \DateTime();
        $aRef = clone $ref;
        $bRef = clone $ref;
        $aRef->add($a);
        $bRef->add($b);

        return $aRef->getTimestamp() <=> $bRef->getTimestamp();
    }
}
