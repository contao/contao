<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Contao\CoreBundle\EventListener\DoctrineSchemaListener;
use Contao\CoreBundle\Tests\Doctrine\DoctrineTestCase;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Psr\Container\ContainerInterface;

class DoctrineSchemaListenerTest extends DoctrineTestCase
{
    public function testAppendsToAnExistingSchema(): void
    {
        $framework = $this->mockContaoFrameworkWithInstaller(
            [
                'tl_files' => [
                    'TABLE_FIELDS' => [
                        'path' => "`path` varchar(1022) NOT NULL default ''",
                    ],
                ],
            ],
        );

        $schema = new Schema();
        $event = new GenerateSchemaEventArgs($this->createMock(EntityManagerInterface::class), $schema);

        $this->assertFalse($schema->hasTable('tl_files'));

        $dcaSchemaProvider = new DcaSchemaProvider(
            $framework,
            $this->mockDoctrineRegistry(),
        );

        $listener = new DoctrineSchemaListener($dcaSchemaProvider, $this->createMock(ContainerInterface::class));
        $listener->postGenerateSchema($event);

        $this->assertTrue($schema->hasTable('tl_files'));
        $this->assertTrue($schema->getTable('tl_files')->hasColumn('path'));
    }
}
