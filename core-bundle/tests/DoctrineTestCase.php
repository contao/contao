<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Database\Installer;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\MySqlSchemaManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;

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
     * @param null $filter
     *
     * @return Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockDoctrineRegistry($filter = null)
    {
        $config = $this->createMock(Configuration::class);

        $config
            ->method('getFilterSchemaAssetsExpression')
            ->willReturn($filter)
        ;

        $connection = $this->createMock(Connection::class);

        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform())
        ;

        $connection
            ->method('getConfiguration')
            ->willReturn($config)
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
     * Mocks a Doctrine registry with database connection and ORM.
     *
     * @param array  $metadata
     * @param string $filter
     *
     * @return Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockDoctrineRegistryWithOrm(array $metadata = [], $filter = null)
    {
        $config = $this->createMock(Configuration::class);

        $config
            ->method('getFilterSchemaAssetsExpression')
            ->willReturn($filter)
        ;

        $connection = $this->createMock(Connection::class);

        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform())
        ;

        $connection
            ->expects(!empty($metadata) ? $this->once() : $this->never())
            ->method('getSchemaManager')
            ->willReturn(new MySqlSchemaManager($connection))
        ;

        $connection
            ->method('getConfiguration')
            ->willReturn($config)
        ;

        $factory = $this->createMock(ClassMetadataFactory::class);

        $factory
            ->method('getAllMetadata')
            ->willReturn($metadata)
        ;

        $configuration = $this->createMock(Configuration::class);

        $configuration
            ->method('getQuoteStrategy')
            ->willReturn(new DefaultQuoteStrategy())
        ;

        $eventManager = $this->createMock(EventManager::class);

        $eventManager
            ->method('hasListeners')
            ->willReturn(false)
        ;

        $em = $this->createMock(EntityManagerInterface::class);

        $em
            ->method('getMetadataFactory')
            ->willReturn($factory)
        ;

        $em
            ->method('getConnection')
            ->willReturn($connection)
        ;

        $em
            ->method('getConfiguration')
            ->willReturn($configuration)
        ;

        $em
            ->method('getEventManager')
            ->willReturn($eventManager)
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
            ->willReturn([$em])
        ;

        $registry
            ->method('getManager')
            ->willReturn($em)
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
