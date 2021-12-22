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

class DbafsFactory
{
    private HashGeneratorInterface $hashGenerator;
    private Connection $connection;
    private EventDispatcherInterface $eventDispatcher;

    /**
     * @internal
     */
    public function __construct(HashGeneratorInterface $hashGenerator, Connection $connection, EventDispatcherInterface $eventDispatcher)
    {
        $this->hashGenerator = $hashGenerator;
        $this->connection = $connection;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(VirtualFilesystemInterface $filesystem, string $table): Dbafs
    {
        return new Dbafs($this->hashGenerator, $this->connection, $this->eventDispatcher, $filesystem, $table);
    }
}
