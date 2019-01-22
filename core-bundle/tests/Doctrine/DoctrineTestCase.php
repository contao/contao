<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Doctrine;

use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Contao\CoreBundle\Framework\ContaoFramework;
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
use PHPUnit\Framework\MockObject\MockObject;

abstract class DoctrineTestCase extends TestCase
{
    /**
     * Mocks a Doctrine registry with database connection.
     *
     * @return Registry|MockObject
     */
    protected function mockDoctrineRegistry(Statement $statement = null, string $filter = null): Registry
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager
            ->method('tablesExist')
            ->willReturn(true)
        ;

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
                        'charset' => 'utf8mb4',
                        'collate' => 'utf8mb4_unicode_ci',
                    ],
                ]
            )
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
     * @return Registry|MockObject
     */
    protected function mockDoctrineRegistryWithOrm(array $metadata = [], string $filter = null): Registry
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
     * Mocks the Contao framework with the database installer.
     *
     * @return ContaoFramework|MockObject
     */
    protected function mockContaoFrameworkWithInstaller(array $dca = [], array $file = []): ContaoFramework
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

    protected function getProvider(array $dca = [], array $file = [], Statement $statement = null, string $filter = null): DcaSchemaProvider
    {
        return new DcaSchemaProvider(
            $this->mockContaoFrameworkWithInstaller($dca, $file),
            $this->mockDoctrineRegistry($statement, $filter)
        );
    }
}
