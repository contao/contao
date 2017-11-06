<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Doctrine;

use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Database\Installer;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;

abstract class DoctrineTestCase extends TestCase
{
    /**
     * Mocks a Doctrine registry with database connection.
     *
     * @return Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockDoctrineRegistry(): Registry
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
     * Mocks the Contao framework with the database installer.
     *
     * @param array $dca
     * @param array $file
     *
     * @return ContaoFrameworkInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockContaoFrameworkWithInstaller(array $dca = [], array $file = []): ContaoFrameworkInterface
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

        $framework = $this->mockContaoFramework();

        $framework
            ->method('createInstance')
            ->willReturn($installer)
        ;

        return $framework;
    }

    /**
     * @param array $dca
     * @param array $file
     *
     * @return DcaSchemaProvider
     */
    protected function getProvider(array $dca = [], array $file = []): DcaSchemaProvider
    {
        return new DcaSchemaProvider(
            $this->mockContaoFrameworkWithInstaller($dca, $file),
            $this->mockDoctrineRegistry()
        );
    }
}
