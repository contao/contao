<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Backend\Provider;

use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\IndexUpdateConfig\UpdateAllProvidersConfig;
use Contao\CoreBundle\Search\Backend\Provider\FilesProvider;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class FilesProviderTest extends AbstractProviderTestCase
{
    public function testSupports(): void
    {
        $provider = new FilesProvider(
            $this->createMock(Connection::class),
            $this->createMock(AccessDecisionManagerInterface::class),
        );

        $this->assertTrue($provider->supportsType(FilesProvider::TYPE));
        $this->assertFalse($provider->supportsType('foobar'));
    }

    public function testUpdateIndex(): void
    {
        $row = [
            'id' => 42,
            'name' => 'super-file.jpg',
            'path' => 'files/folder/super-file.jpg',
            'extension' => 'jpg',
        ];

        $connection = $this->createInMemorySQLiteConnection(
            [
                new Table('tl_files', [
                    new Column('id', Type::getType(Types::INTEGER)),
                    new Column('name', Type::getType(Types::STRING)),
                    new Column('path', Type::getType(Types::STRING)),
                    new Column('extension', Type::getType(Types::STRING)),
                ]),
            ],
            [
                'tl_files' => [$row],
            ],
        );

        $provider = new FilesProvider(
            $connection,
            $this->createMock(AccessDecisionManagerInterface::class),
        );

        $documents = $provider->updateIndex(new UpdateAllProvidersConfig());

        /** @var Document $document */
        $document = iterator_to_array($documents)[0];

        $this->assertSame('42', $document->getId());
        $this->assertSame(FilesProvider::TYPE, $document->getType());
        $this->assertSame(['extension:jpg'], $document->getTags());
        $this->assertSame($row, $document->getMetadata());
    }
}
