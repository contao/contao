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
     * The data yielded is streamed into the backup file. Every yielded string will be
     * a new line.
     */
    public function dump(Connection $connection, CreateConfig $config): \Generator;
}
