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
use Contao\CoreBundle\Doctrine\Schema\SchemaProvider;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Database\Installer;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use PHPUnit\Framework\MockObject\MockObject;

abstract class DoctrineTestCase extends TestCase
{
    /**
     * Mocks a Doctrine registry with database connection.
     *
     * @return Registry&MockObject
     */
    protected function mockDoctrineRegistry(Statement $statement = null): Registry
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform())
        ;

        $connection
            ->method('executeQuery')
            ->willReturn($statement)
        ;

        $connection
            ->method('getParams')
            ->willReturn(['defaultTableOptions' => $this->getDefaultTableOptions()])
        ;

        $connection
            ->method('getConfiguration')
            ->willReturn($this->createMock(Configuration::class))
        ;

        $registry = $this->createMock(Registry::class);
        $registry
            ->method('getConnection')
            ->willReturn($connection)
        ;

        return $registry;
    }

    /**
     * Mocks the Contao framework with the database installer.
     *
     * @return ContaoFramework&MockObject
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

    protected function getDcaSchemaProvider(array $dca = [], array $file = [], Statement $statement = null): DcaSchemaProvider
    {
        return new DcaSchemaProvider(
            $this->mockContaoFrameworkWithInstaller($dca, $file),
            $this->mockDoctrineRegistry($statement),
            $this->createMock(SchemaProvider::class)
        );
    }

    /**
     * Returns an empty Schema which has the default table options set.
     */
    protected function getSchema(): Schema
    {
        $schemaConfig = new SchemaConfig();
        $schemaConfig->setDefaultTableOptions($this->getDefaultTableOptions());

        return new Schema([], [], $schemaConfig);
    }

    /**
     * Returns an EntityManager configured to load the annotated entities in
     * the tests/Fixture/Entity directory.
     */
    protected function getTestEntityManager(): EntityManager
    {
        $params = [
            'driver' => 'mysqli',
        ];

        $driverChain = new MappingDriverChain();
        $driverChain->addDriver(
            new AnnotationDriver(
                new AnnotationReader(),
                __DIR__.'/../Fixtures/Entity'
            ),
            'Contao\\CoreBundle\\Tests\\Fixtures\\Entity'
        );

        $config = new Configuration();
        $config->setEntityNamespaces(['ContaoTestsDoctrine' => 'Contao\CoreBundle\Tests\Fixtures\Entity']);
        $config->setAutoGenerateProxyClasses(true);
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('ContaoTests\Doctrine');
        $config->setMetadataDriverImpl($driverChain);

        return EntityManager::create($params, $config);
    }

    private function getDefaultTableOptions(): array
    {
        return [
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
        ];
    }
}
