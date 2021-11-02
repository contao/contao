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

use Contao\CoreBundle\Doctrine\Backup\Config\CreateConfig;
use Doctrine\DBAL\Connection;

interface DumperInterface
{
    /**
     * Dumpers are expected to write $config->getDumpHeader() as the first line.
     *
     * @throws BackupManagerException in case anything went wrong
     */
    public function dump(Connection $connection, CreateConfig $config): void;
}
