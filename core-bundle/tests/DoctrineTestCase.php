<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Database\Installer;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;

/**
 * Abstract DoctrineTestCase class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
abstract class DoctrineTestCase extends TestCase
{
    /**
     * Returns a Doctrine registry with database connection.
     *
     * @return Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockDoctrineRegistry()
    {
        $connection = $this->createMock(Connection::class);

        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform())
        ;

        $registry = $this->createMock(Registry::class);

        $registry
            ->method('getConnection')
            ->willReturn($connection)
        ;

        $registry
            ->method('getConnections')
            ->willReturn([$connection])
        ;

        $registry
            ->method('getManagerNames')
            ->willReturn([])
        ;

        return $registry;
    }

    /**
     * Returns a Doctrine registry with database installer.
     *
     * @param array $dca
     * @param array $file
     *
     * @return ContaoFrameworkInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockContaoFrameworkWithInstaller(array $dca = [], array $file = [])
    {
        $installer = $this->createMock(Installer::class);

        $installer
            ->method('getFromDca')
            ->willReturn($dca)
        ;

        $installer
            ->method('getFromFile')
            ->willReturn($file)
        ;

        return $this->mockContaoFramework(null, null, [], [Installer::class => $installer]);
    }
}
