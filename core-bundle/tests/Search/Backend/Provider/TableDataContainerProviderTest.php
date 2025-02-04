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

use Contao\Config;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\DataContainer\DcaUrlAnalyzer;
use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\Provider\TableDataContainerProvider;
use Contao\CoreBundle\Search\Backend\ReindexConfig;
use Contao\DcaLoader;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class TableDataContainerProviderTest extends AbstractProviderTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME'], $GLOBALS['TL_LANG'], $GLOBALS['TL_DCA']);

        $this->resetStaticProperties([System::class, Config::class, DcaLoader::class]);

        (new Filesystem())->remove(Path::join($this->getFixturesDir(), 'var/cache'));

        parent::tearDown();
    }

    public function testSupports(): void
    {
        $provider = new TableDataContainerProvider(
            $this->mockContaoFramework(),
            $this->createMock(ResourceFinder::class),
            $this->createMock(Connection::class),
            $this->createMock(AccessDecisionManagerInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(DcaUrlAnalyzer::class),
        );

        $this->assertTrue($provider->supportsType(TableDataContainerProvider::TYPE_PREFIX.'foobar'));
        $this->assertFalse($provider->supportsType('foobar'));
    }

    public function testUpdateIndex(): void
    {
        $connection = $this->createInMemorySQLiteConnection(
            [
                new Table('tl_content', [
                    new Column('id', Type::getType(Types::INTEGER)),
                    new Column('type', Type::getType(Types::STRING)),
                    new Column('text', Type::getType(Types::STRING)),
                ]),
                new Table('tl_news', [
                    new Column('id', Type::getType(Types::INTEGER)),
                    new Column('headline', Type::getType(Types::STRING)),
                    new Column('teaser', Type::getType(Types::STRING)),
                ]),
            ],
            [
                'tl_content' => [
                    [
                        'id' => 1,
                        'type' => 'text',
                        'text' => '<p>This is <em>some</em> content.',
                    ],
                ],
                'tl_news' => [
                    [
                        'id' => 2,
                        'headline' => 'This is my news 2!',
                        'teaser' => 'A great teaser!',
                    ],
                    [
                        'id' => 3,
                        'headline' => 'This is my news 3!',
                        'teaser' => 'Another great teaser!',
                    ],
                ],
            ],
        );

        $framework = $this->mockContaoFramework();

        $fixturesDir = $this->getFixturesDir();
        $resourceFinder = new ResourceFinder(Path::join($fixturesDir, 'table-data-container-provider'));
        $locator = new FileLocator(Path::join($fixturesDir, 'table-data-container-provider'));

        $container = $this->getContainerWithContaoConfiguration($fixturesDir);
        $container->set('contao.resource_finder', $resourceFinder);
        $container->set('contao.resource_locator', $locator);

        System::setContainer($container);

        $provider = new TableDataContainerProvider(
            $framework,
            $resourceFinder,
            $connection,
            $this->createMock(AccessDecisionManagerInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(DcaUrlAnalyzer::class),
        );

        $documentsIterator = $provider->updateIndex(new ReindexConfig());

        // Sort the documents for deterministic tests
        /** @var array<Document> $documents */
        $documents = iterator_to_array($documentsIterator);
        usort($documents, static fn (Document $a, Document $b) => $a->getId() <=> $b->getId());

        $this->assertSame('1', $documents[0]->getId());
        $this->assertSame('contao.db.tl_content', $documents[0]->getType());
        $this->assertSame('tl_content', $documents[0]->getMetadata()['table']);
        $this->assertSame('<p>This is <em>some</em> content.', $documents[0]->getSearchableContent());
        $this->assertSame('2', $documents[1]->getId());
        $this->assertSame('contao.db.tl_news', $documents[1]->getType());
        $this->assertSame('tl_news', $documents[1]->getMetadata()['table']);
        $this->assertSame('This is my news 2! A great teaser!', $documents[1]->getSearchableContent());
        $this->assertSame('3', $documents[2]->getId());
        $this->assertSame('contao.db.tl_news', $documents[2]->getType());
        $this->assertSame('tl_news', $documents[2]->getMetadata()['table']);
        $this->assertSame('This is my news 3! Another great teaser!', $documents[2]->getSearchableContent());
    }
}
