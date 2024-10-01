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

interface RetentionPolicyInterface
{
    /**
     * The retention policy gets the latest backup as well as all backups (which also
     * contain the latest and is ordered by date created DESC) and is expected to
     * return an array of backups which shall be kept.
     *
     * @param array<Backup> $allBackups
     *
     * @return array<Backup>
     */
    public function apply(Backup $latestBackup, array $allBackups): array;
}
