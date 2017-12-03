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
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\MySqlSchemaManager;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;

abstract class DoctrineTestCase extends TestCase
{
    /**
     * Mocks a Doctrine registry with database connection.
     *
     * @param Statement|null $statement
     *
     * @return Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockDoctrineRegistry(Statement $statement = null): Registry
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $schemaManager
            ->method('tablesExist')
            ->willReturn(true)
        ;

        $connection = $this->createMock(Connection::class);

        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform())
        ;

        $connection
            ->method('query')
            ->willReturn($statement)
        ;

        $connection
            ->method('getSchemaManager')
            ->willReturn($schemaManager)
        ;

        $connection
            ->method('getParams')
            ->willReturn(
                [
                    'defaultTableOptions' => [
                        'collate' => 'utf8mb4_unicode_ci',
                    ],
                ]
            )
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
     * @param array $metadata
     *
     * @return Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockDoctrineRegistryWithOrm(array $metadata = []): Registry
    {
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
     * @param array          $dca
     * @param array          $file
     * @param Statement|null $statement
     *
     * @return DcaSchemaProvider
     */
    protected function getProvider(array $dca = [], array $file = [], Statement $statement = null): DcaSchemaProvider
    {
        return new DcaSchemaProvider(
            $this->mockContaoFrameworkWithInstaller($dca, $file),
            $this->mockDoctrineRegistry($statement)
        );
    }
}
