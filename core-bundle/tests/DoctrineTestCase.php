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
use Doctrine\Bundle\DoctrineBundle\Registry;
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
        $connection = $this->getMock('Doctrine\DBAL\Connection', ['getDatabasePlatform'], [], '', false);

        $connection
            ->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform())
        ;

        $registry = $this
            ->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $registry
            ->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection)
        ;

        $registry
            ->expects($this->any())
            ->method('getConnections')
            ->willReturn([$connection])
        ;

        $registry
            ->expects($this->any())
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
        $installer = $this->getMock('Contao\Database\Installer', ['getFromDca', 'getFromFile']);

        $installer
            ->expects($this->any())
            ->method('getFromDca')
            ->willReturn($dca)
        ;

        $installer
            ->expects($this->any())
            ->method('getFromFile')
            ->willReturn($file)
        ;

        return $this->mockContaoFramework(null, null, [], ['Contao\Database\Installer' => $installer]);
    }
}
