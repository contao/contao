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
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
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
     * @param Connection&MockObject $connection
     */
    protected function mockDoctrineRegistry(Connection|null $connection = null): Registry&MockObject
    {
        $connection ??= $this->createMock(Connection::class);

        if ($connection instanceof MockObject) {
            $connection
                ->method('getDatabasePlatform')
                ->willReturn(new MySQLPlatform())
            ;

            $connection
                ->method('getParams')
                ->willReturn(['defaultTableOptions' => $this->getDefaultTableOptions()])
            ;

            $connection
                ->method('getConfiguration')
                ->willReturn($this->createMock(Configuration::class))
            ;
        }

        $registry = $this->createMock(Registry::class);
        $registry
            ->method('getConnection')
            ->willReturn($connection)
        ;

        return $registry;
    }

    /**
     * Mocks the Contao framework with the database installer.
     */
    protected function mockContaoFrameworkWithInstaller(array $dca = []): ContaoFramework&MockObject
    {
        $installer = $this->createMock(Installer::class);
        $installer
            ->method('getFromDca')
            ->willReturn($dca)
        ;

        $framework = $this->mockContaoFramework();
        $framework
            ->method('createInstance')
            ->willReturn($installer)
        ;

        return $framework;
    }

    /**
     * @param Connection&MockObject $connection
     */
    protected function getDcaSchemaProvider(array $dca = [], Connection|null $connection = null): DcaSchemaProvider
    {
        return new DcaSchemaProvider(
            $this->mockContaoFrameworkWithInstaller($dca),
            $this->mockDoctrineRegistry($connection),
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
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $driverChain = new MappingDriverChain();
        $driverChain->addDriver(
            new AnnotationDriver(
                new AnnotationReader(),
                __DIR__.'/../Fixtures/Entity',
            ),
            'Contao\\CoreBundle\\Tests\\Fixtures\\Entity',
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
