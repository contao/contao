<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem\Dbafs;

use Contao\CoreBundle\Filesystem\Dbafs\Hashing\HashGeneratorInterface;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @experimental
 */
class DbafsFactory
{
    /**
     * @internal
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(VirtualFilesystemInterface $filesystem, HashGeneratorInterface $hashGenerator, string $table): Dbafs
    {
        return new Dbafs($hashGenerator, $this->connection, $this->eventDispatcher, $filesystem, $table);
    }
}
