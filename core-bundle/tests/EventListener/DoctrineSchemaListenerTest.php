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

use Contao\CoreBundle\Crawl\Escargot\Factory;
use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Contao\CoreBundle\Doctrine\Schema\EscargotSchemaProvider;
use Contao\CoreBundle\EventListener\DoctrineSchemaListener;
use Contao\CoreBundle\Tests\Doctrine\DoctrineTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Terminal42\Escargot\Queue\DoctrineQueue;

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
        $event = new GenerateSchemaEventArgs($this->createStub(EntityManagerInterface::class), $schema);

        $this->assertFalse($schema->hasTable('tl_files'));

        $dcaSchemaProvider = new DcaSchemaProvider(
            $framework,
            $this->mockDoctrineRegistry(),
        );

        $queue = new DoctrineQueue($this->createStub(Connection::class), static fn (): string => 'job-id', 'tl_crawl_queue');

        $factory = $this->createStub(Factory::class);
        $factory
            ->method('createDoctrineQueue')
            ->willReturn($queue)
        ;

        $listener = new DoctrineSchemaListener($dcaSchemaProvider, new EscargotSchemaProvider($factory));
        $listener->postGenerateSchema($event);

        $this->assertTrue($schema->hasTable('tl_files'));
        $this->assertTrue($schema->getTable('tl_files')->hasColumn('path'));
        $this->assertTrue($schema->hasTable('tl_crawl_queue'));
    }
}
