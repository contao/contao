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
use Contao\CoreBundle\DataContainer\VirtualFieldsHandler;
use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\Provider\TableDataContainerProvider;
use Contao\CoreBundle\Search\Backend\ReindexConfig;
use Contao\DcaExtractor;
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
use Symfony\Contracts\Translation\TranslatorInterface;

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
            $this->createContaoFrameworkStub(),
            $this->createStub(ResourceFinder::class),
            $this->createStub(Connection::class),
            $this->createStub(AccessDecisionManagerInterface::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(DcaUrlAnalyzer::class),
            $this->createStub(TranslatorInterface::class),
            $this->createStub(VirtualFieldsHandler::class),
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
                    new Column('jsonData', Type::getType(Types::TEXT)),
                    new Column('emptyTarget', Type::getType(Types::TEXT), ['notnull' => false]),
                    new Column('invalidTarget', Type::getType(Types::TEXT), ['notnull' => false]),
                ]),
                new Table('tl_news', [
                    new Column('id', Type::getType(Types::INTEGER)),
                    new Column('headline', Type::getType(Types::STRING)),
                    new Column('teaser', Type::getType(Types::STRING)),
                ]),
                new Table('tl_undo', [
                    new Column('id', Type::getType(Types::INTEGER)),
                    new Column('data', Type::getType(Types::BLOB)),
                ]),
            ],
            [
                'tl_content' => [
                    [
                        'id' => 1,
                        'type' => 'text',
                        'text' => '<p>This is <em>some</em> content.',
                        'jsonData' => json_encode(['foo' => 'bar', 'moo' => 'koo'], JSON_THROW_ON_ERROR),
                        'emptyTarget' => null,
                        'invalidTarget' => 'this is not JSON',
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
                'tl_undo' => [
                    [
                        'id' => 1,
                        'data' => 'This should be ignored!',
                    ],
                ],
            ],
        );

        $contentDcaExtractor = $this->createStub(DcaExtractor::class);
        $contentDcaExtractor
            ->method('getVirtualFields')
            ->willReturn(['foo' => 'jsonData', 'moo' => 'jsonData', 'lorem' => 'emptyTarget', 'ipsum' => 'invalidTarget'])
        ;

        $generalDcaExtractor = $this->createStub(DcaExtractor::class);
        $generalDcaExtractor
            ->method('getVirtualFields')
            ->willReturn([])
        ;

        $framework = $this->createContaoFrameworkStub([], [DcaExtractor::class => static fn (array $args) => 'tl_content' === $args[0] ? $contentDcaExtractor : $generalDcaExtractor]);

        $fixturesDir = $this->getFixturesDir();
        $resourceFinder = new ResourceFinder(Path::join($fixturesDir, 'table-data-container-provider'));
        $locator = new FileLocator(Path::join($fixturesDir, 'table-data-container-provider'));

        $virtualFieldsHandler = $this->createStub(VirtualFieldsHandler::class);
        $virtualFieldsHandler
            ->method('expandFields')
            ->willReturnCallback(
                static function (array $record): array {
                    if ($record['jsonData'] ?? null) {
                        return [...$record, ...json_decode($record['jsonData'], true, flags: JSON_THROW_ON_ERROR)];
                    }

                    return $record;
                },
            )
        ;

        $container = $this->getContainerWithContaoConfiguration($fixturesDir);
        $container->set('contao.resource_finder', $resourceFinder);
        $container->set('contao.resource_locator', $locator);

        System::setContainer($container);

        $provider = new TableDataContainerProvider(
            $framework,
            $resourceFinder,
            $connection,
            $this->createStub(AccessDecisionManagerInterface::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(DcaUrlAnalyzer::class),
            $this->createStub(TranslatorInterface::class),
            $virtualFieldsHandler,
        );

        $documentsIterator = $provider->updateIndex(new ReindexConfig());

        // Sort the documents for deterministic tests
        /** @var array<Document> $documents */
        $documents = iterator_to_array($documentsIterator);
        usort($documents, static fn (Document $a, Document $b) => $a->getId() <=> $b->getId());

        $this->assertCount(3, $documents);
        $this->assertSame('1', $documents[0]->getId());
        $this->assertSame('contao.db.tl_content', $documents[0]->getType());
        $this->assertSame('tl_content', $documents[0]->getMetadata()['table']);
        $this->assertSame('<p>This is <em>some</em> content. bar koo', $documents[0]->getSearchableContent());
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
