<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Doctrine\Schema;

use Contao\CoreBundle\Doctrine\Schema\SchemaProvider;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Tools\SchemaTool;

class SchemaProviderTest extends TestCase
{
    public function testCreateSchemaGetsSchemaFromMetadata(): void
    {
        $metadata = [
            new ClassMetadata('EntityA'),
            new ClassMetadata('EntityB'),
        ];

        $schema = $this->createMock(Schema::class);

        $schemaProvider = new SchemaProvider(
            $this->mockEntityManager($metadata),
            $this->mockSchemaTool($metadata, $schema)
        );

        $this->assertSame($schema, $schemaProvider->createSchema());
    }

    private function mockSchemaTool(array $metadata, Schema $schema)
    {
        $schemaTool = $this->createMock(SchemaTool::class);
        $schemaTool
            ->expects($this->once())
            ->method('getSchemaFromMetadata')
            ->with($metadata)
            ->willReturn($schema)
        ;

        return $schemaTool;
    }

    private function mockEntityManager(array $metadata)
    {
        $factory = $this->createMock(ClassMetadataFactory::class);
        $factory
            ->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn($metadata)
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($factory)
        ;

        return $entityManager;
    }
}
