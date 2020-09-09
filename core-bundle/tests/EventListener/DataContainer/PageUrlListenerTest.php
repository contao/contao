<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\EventListener\DataContainer\PageUrlListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Slug\Slug;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\Input;
use Contao\PageModel;
use Contao\Search;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\FetchMode;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageUrlListenerTest extends TestCase
{
    /**
     * @dataProvider generatesAliasProvider
     */
    public function testGeneratesAlias(array $activeRecord, string $expectedAlias): void
    {
        /** @var MockObject&PageModel $page */
        $page = $this->mockClassWithProperties(PageModel::class, $activeRecord);

        $pageAdapter = $this->mockAdapter(['findWithDetails']);
        $pageAdapter
            ->expects($this->once())
            ->method('findWithDetails')
            ->with($page->id)
            ->willReturn($page)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);

        $slug = $this->createMock(Slug::class);
        $slug
            ->expects($this->once())
            ->method('generate')
            ->with($page->title, $page->id, $this->isType('callable'))
            ->willReturn($page->alias)
        ;

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => $page->id,
                'activeRecord' => (object) $activeRecord,
            ]
        );

        $listener = new PageUrlListener(
            $framework,
            $slug,
            $this->createMock(TranslatorInterface::class),
            $this->mockConnectionWithStatement()
        );

        $this->assertSame($expectedAlias, $listener->generateAlias('', $dc));
    }

    public function generatesAliasProvider(): \Generator
    {
        yield [
            [
                'id' => 17,
                'title' => 'Foo',
                'alias' => 'foo',
                'useFolderUrl' => false,
                'folderUrl' => '',
            ],
            'foo',
        ];

        yield [
            [
                'id' => 22,
                'title' => 'Bar',
                'alias' => 'bar',
                'useFolderUrl' => false,
                'folderUrl' => '',
            ],
            'bar',
        ];

        yield [
            [
                'id' => 17,
                'title' => 'Foo',
                'alias' => 'foo',
                'useFolderUrl' => true,
                'folderUrl' => 'bar/',
            ],
            'bar/foo',
        ];
    }

    /**
     * @dataProvider duplicateAliasProvider
     */
    public function testChecksForDuplicatesWhenGeneratingAlias(array $activeRecord, array $pages, array $aliasIds, string $value, string $generated, string $expectedQuery, bool $expectExists): void
    {
        $framework = $this->mockFrameworkWithPages([], $activeRecord, ...$pages);

        $slug = $this->createMock(Slug::class);
        $slug
            ->expects($this->once())
            ->method('generate')
            ->with(
                $activeRecord['title'],
                $activeRecord['id'],
                $this->callback(
                    function (callable $callback) use ($generated, $expectExists) {
                        $this->assertSame(
                            $expectExists,
                            $callback($generated)
                        );

                        return true;
                    }
                )
            )
            ->willReturn($generated)
        ;

        $connection = $this->mockConnection(
            array_merge([$activeRecord], $pages),
            [$activeRecord['id']],
            [$expectedQuery],
            [$aliasIds]
        );

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => $activeRecord['id'],
                'activeRecord' => (object) $activeRecord,
            ]
        );

        $listener = new PageUrlListener(
            $framework,
            $slug,
            $this->createMock(TranslatorInterface::class),
            $connection
        );

        $listener->generateAlias('', $dc);
    }

    /**
     * @dataProvider duplicateAliasProvider
     */
    public function testChecksForDuplicatesWhenValidatingAlias(array $activeRecord, array $pages, array $aliasIds, string $value, string $generated, string $expectQuery, bool $expectExists): void
    {
        $framework = $this->mockFrameworkWithPages([], $activeRecord, ...$pages);

        $slug = $this->createMock(Slug::class);
        $slug
            ->expects($this->never())
            ->method('generate')
        ;

        $connection = $this->mockConnection(
            array_merge([$activeRecord], $pages),
            [$activeRecord['id']],
            [$expectQuery],
            [$aliasIds]
        );

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => $activeRecord['id'],
                'activeRecord' => (object) $activeRecord,
            ]
        );

        $urlPrefix = $activeRecord['urlPrefix'] ? $activeRecord['urlPrefix'].'/' : '';
        $url = '/'.$urlPrefix.$value.$activeRecord['urlSuffix'];
        $translator = $this->mockTranslator('ERR.pageUrlExists', $expectExists ? $url : null);

        $listener = new PageUrlListener(
            $framework,
            $slug,
            $translator,
            $connection
        );

        $listener->generateAlias($value, $dc);
    }

    public function duplicateAliasProvider(): \Generator
    {
        yield 'No duplicate aliases' => [
            [
                'id' => 1,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => '',
                'urlSuffix' => '',
            ],
            [],
            [],
            'foo',
            'foo',
            'foo',
            false,
        ];

        yield 'in same root' => [
            [
                'id' => 1,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => '',
                'urlSuffix' => '',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => '',
                'urlSuffix' => '',
            ]],
            [2],
            'foo',
            'foo',
            'foo',
            true,
        ];

        yield 'in same root with prefix' => [
            [
                'id' => 1,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => 'de',
                'urlSuffix' => '',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => 'de',
                'urlSuffix' => '',
            ]],
            [2],
            'foo',
            'foo',
            'foo',
            true,
        ];

        yield 'in same root with suffix' => [
            [
                'id' => 1,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ]],
            [2],
            'foo',
            'foo',
            'foo',
            true,
        ];

        yield 'in same root with prefix and suffix' => [
            [
                'id' => 1,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => 'de',
                'urlSuffix' => '.html',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => 'de',
                'urlSuffix' => '.html',
            ]],
            [2],
            'foo',
            'foo',
            'foo',
            true,
        ];

        yield 'in separate root without language prefix' => [
            [
                'id' => 1,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => '',
                'urlSuffix' => '',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 2,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => '',
                'urlSuffix' => '',
            ]],
            [2],
            'foo',
            'foo',
            'foo',
            true,
        ];

        yield 'in separate root with language prefix' => [
            [
                'id' => 1,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => 'de',
                'urlSuffix' => '',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 2,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => 'fr',
                'urlSuffix' => '',
            ]],
            [2],
            'foo',
            'foo',
            'foo',
            false,
        ];

        yield 'in separate domain' => [
            [
                'id' => 1,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => '',
                'urlSuffix' => '',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 2,
                'useFolderUrl' => false,
                'domain' => 'example.com',
                'urlPrefix' => '',
                'urlSuffix' => '',
            ]],
            [2],
            'foo',
            'foo',
            'foo',
            false,
        ];

        yield 'with separate url suffix' => [
            [
                'id' => 1,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 2,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => '',
                'urlSuffix' => '',
            ]],
            [2],
            'foo',
            'foo',
            'foo',
            false,
        ];

        yield 'with same prefix but separate url suffix' => [
            [
                'id' => 1,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => 'de',
                'urlSuffix' => '.html',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 2,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => 'de',
                'urlSuffix' => '',
            ]],
            [2],
            'foo',
            'foo',
            'foo',
            false,
        ];

        yield 'with prefix fragment' => [
            [
                'id' => 1,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => 'de/ch',
                'urlSuffix' => '.html',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'ch/foo',
                'rootId' => 2,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => 'de',
                'urlSuffix' => '.html',
            ]],
            [2],
            'foo',
            'foo',
            'foo',
            true,
        ];

        yield 'with prefix fragment inverted' => [
            [
                'id' => 1,
                'title' => 'Foo',
                'alias' => 'ch/foo',
                'rootId' => 1,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => 'de',
                'urlSuffix' => '.html',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 2,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => 'de/ch',
                'urlSuffix' => '.html',
            ]],
            [2],
            'ch/foo',
            'ch/foo',
            'foo',
            true,
        ];

        yield 'with suffix fragment' => [
            [
                'id' => 1,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo.ht',
                'rootId' => 2,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => '',
                'urlSuffix' => 'ml',
            ]],
            [2],
            'foo',
            'foo',
            'foo',
            true,
        ];

        yield 'with suffix fragment inverted' => [
            [
                'id' => 1,
                'title' => 'Foo',
                'alias' => 'foo.ht',
                'rootId' => 1,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => '',
                'urlSuffix' => 'ml',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 2,
                'useFolderUrl' => false,
                'domain' => '',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ]],
            [2],
            'foo.ht',
            'foo.ht',
            'foo',
            true,
        ];

        yield 'with folderUrl match' => [
            [
                'id' => 1,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => true,
                'folderUrl' => 'bar/',
                'domain' => '',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'bar/foo',
                'rootId' => 2,
                'useFolderUrl' => true,
                'folderUrl' => '',
                'domain' => '',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ]],
            [2],
            'bar/foo',
            'foo',
            'bar/foo',
            true,
        ];

        yield 'with folderUrl mismatch' => [
            [
                'id' => 1,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => true,
                'folderUrl' => 'baz/',
                'domain' => '',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'bar/foo',
                'rootId' => 2,
                'useFolderUrl' => true,
                'folderUrl' => '',
                'domain' => '',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ]],
            [2],
            'baz/foo',
            'foo',
            'baz/foo',
            false,
        ];

        yield 'everything' => [
            [
                'id' => 1,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => true,
                'folderUrl' => 'bar/',
                'domain' => '',
                'urlPrefix' => 'ch/de',
                'urlSuffix' => '.html',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'de/bar/foo.ht',
                'rootId' => 2,
                'useFolderUrl' => true,
                'folderUrl' => '',
                'domain' => '',
                'urlPrefix' => 'ch',
                'urlSuffix' => 'ml',
            ]],
            [2],
            'bar/foo',
            'foo',
            'bar/foo',
            true,
        ];

        yield 'ignores alias page not found' => [
            [
                'id' => 1,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => true,
                'folderUrl' => 'baz/',
                'domain' => '',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'bar/foo',
                'rootId' => 2,
                'useFolderUrl' => true,
                'folderUrl' => '',
                'domain' => '',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ]],
            [3],
            'baz/foo',
            'foo',
            'baz/foo',
            false,
        ];
    }

    public function testPurgesTheSearchIndexOnAliasChange(): void
    {
        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->once())
            ->method('fetchAll')
            ->willReturn(['uri'])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT url FROM tl_search WHERE pid=:pageId', ['pageId' => 17])
            ->willReturn($statement)
        ;

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => 17,
                'activeRecord' => (object) [
                    'alias' => 'foo',
                ],
            ]
        );

        $listener = new PageUrlListener(
            $this->mockContaoFramework([Search::class => $search]),
            $this->createMock(Slug::class),
            $this->createMock(TranslatorInterface::class),
            $connection
        );

        $listener->purgeSearchIndexOnAliasChange('bar', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexWithUnchangedAlias(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => 17,
                'activeRecord' => (object) [
                    'alias' => 'foo',
                ],
            ]
        );

        $listener = new PageUrlListener(
            $this->mockContaoFramework([Search::class => $search]),
            $this->createMock(Slug::class),
            $this->createMock(TranslatorInterface::class),
            $connection
        );

        $listener->purgeSearchIndexOnAliasChange('foo', $dc);
    }

    public function testPurgesTheSearchIndexOnDelete(): void
    {
        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->once())
            ->method('fetchAll')
            ->willReturn(['uri'])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT url FROM tl_search WHERE pid=:pageId', ['pageId' => 17])
            ->willReturn($statement)
        ;

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => 17,
            ]
        );

        $listener = new PageUrlListener(
            $this->mockContaoFramework([Search::class => $search]),
            $this->createMock(Slug::class),
            $this->createMock(TranslatorInterface::class),
            $connection
        );

        $listener->purgeSearchIndexOnDelete($dc);
    }

    public function testDoesNotPurgeTheSearchIndexWithoutId(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => null,
            ]
        );

        $listener = new PageUrlListener(
            $this->mockContaoFramework([Search::class => $search]),
            $this->createMock(Slug::class),
            $this->createMock(TranslatorInterface::class),
            $connection
        );

        $listener->purgeSearchIndexOnDelete($dc);
    }

    public function testResetsThePrefixesAndSuffixes(): void
    {
        $framework = $this->mockFrameworkWithPages(
            [],
            [
                'id' => 1,
                'alias' => 'foo',
                'domain' => '',
                'urlPrefix' => '',
                'urlSuffix' => '',
            ],
            [
                'id' => 2,
                'alias' => 'bar',
                'domain' => '',
                'urlPrefix' => '',
                'urlSuffix' => '',
            ],
            [
                'id' => 3,
                'alias' => 'baz',
                'domain' => '',
                'urlPrefix' => '',
                'urlSuffix' => '',
            ]
        );

        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->exactly(5))
            ->method('fetchAll')
            ->willReturn([])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(5))
            ->method('executeQuery')
            ->withConsecutive(
                [
                    "SELECT urlPrefix, urlSuffix FROM tl_page WHERE type='root'",
                ],
                [
                    'SELECT id FROM tl_page WHERE alias LIKE :alias AND id!=:id',
                    [
                        'alias' => '%foo%',
                        'id' => 1,
                    ],
                ],
                [
                    'SELECT id FROM tl_page WHERE alias LIKE :alias AND id!=:id',
                    [
                        'alias' => '%bar%',
                        'id' => 2,
                    ],
                ],
                [
                    "SELECT urlPrefix, urlSuffix FROM tl_page WHERE type='root'",
                ],
                [
                    'SELECT id FROM tl_page WHERE alias LIKE :alias AND id!=:id',
                    [
                        'alias' => '%baz%',
                        'id' => 3,
                    ],
                ]
            )
            ->willReturn($statement)
        ;

        $listener = new PageUrlListener(
            $framework,
            $this->createMock(Slug::class),
            $this->createMock(TranslatorInterface::class),
            $connection
        );

        /** @var DataContainer&MockObject $dc1 */
        $dc1 = $this->mockClassWithProperties(DataContainer::class, ['id' => 1]);

        /** @var DataContainer&MockObject $dc2 */
        $dc2 = $this->mockClassWithProperties(DataContainer::class, ['id' => 2]);

        /** @var DataContainer&MockObject $dc3 */
        $dc3 = $this->mockClassWithProperties(DataContainer::class, ['id' => 3]);

        $listener->generateAlias('foo', $dc1);
        $listener->generateAlias('bar', $dc2);

        $listener->reset();
        $listener->generateAlias('baz', $dc3);
    }

    public function testReturnsValueWhenValidatingUrlPrefix(): void
    {
        $framework = $this->mockFrameworkWithPages(
            [
                'dns' => '',
                'urlPrefix' => 'de',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 1,
                'pid' => 0,
                'type' => 'root',
                'alias' => 'root',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 2,
                'pid' => 1,
                'alias' => 'foo',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ]
        );

        $connection = $this->mockConnection(
            [['urlPrefix' => 'de', 'urlSuffix' => '.html']],
            [2],
            ['foo'],
            [[]],
            true
        );

        $listener = new PageUrlListener(
            $framework,
            $this->createMock(Slug::class),
            $this->mockTranslator(),
            $connection
        );

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => 1,
                'activeRecord' => (object) ['type' => 'root', 'dns' => '', 'urlPrefix' => 'de', 'urlSuffix' => ''],
            ]
        );

        $listener->validateUrlPrefix('en', $dc);
    }

    public function testThrowsExceptionOnDuplicateUrlPrefixInDomain(): void
    {
        $translator = $this->mockTranslator('ERR.urlPrefixExists', 'en');

        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(1)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with(
                "SELECT COUNT(*) FROM tl_page WHERE urlPrefix=:urlPrefix AND dns=:dns AND id!=:rootId AND type='root'",
                ['urlPrefix' => 'en', 'dns' => 'www.example.com', 'rootId' => 1]
            )
            ->willReturn($statement)
        ;

        $listener = new PageUrlListener(
            $this->mockContaoFramework(),
            $this->createMock(Slug::class),
            $translator,
            $connection
        );

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => 1,
                'activeRecord' => (object) [
                    'type' => 'root',
                    'dns' => 'www.example.com',
                    'urlPrefix' => 'de',
                    'urlSuffix' => '',
                ],
            ]
        );

        $listener->validateUrlPrefix('en', $dc);
    }

    public function testThrowsExceptionIfUrlPrefixLeadsToDuplicatePages(): void
    {
        $framework = $this->mockFrameworkWithPages(
            [
                'dns' => '',
                'urlPrefix' => 'de',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 1,
                'pid' => 0,
                'type' => 'root',
                'alias' => 'root',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 2,
                'pid' => 1,
                'alias' => 'foo',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 3,
                'pid' => 1,
                'alias' => 'bar',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 4,
                'pid' => 3,
                'alias' => 'bar/foo',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 5,
                'pid' => 0,
                'type' => 'root',
                'urlPrefix' => 'de',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 6,
                'pid' => 5,
                'alias' => 'bar/foo',
                'urlPrefix' => 'de',
                'urlSuffix' => '.html',
            ]
        );

        // Expects exception
        $translator = $this->mockTranslator('ERR.pageUrlPrefix', '/de/bar/foo.html');

        $connection = $this->mockConnection(
            [['urlPrefix' => 'de', 'urlSuffix' => '.html']],
            [2, 3, 4],
            ['foo', 'bar', 'bar/foo'],
            [[], [], [6]],
            true
        );

        $listener = new PageUrlListener(
            $framework,
            $this->createMock(Slug::class),
            $translator,
            $connection
        );

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => 1,
                'activeRecord' => (object) ['type' => 'root', 'dns' => '', 'urlPrefix' => 'de', 'urlSuffix' => ''],
            ]
        );

        $listener->validateUrlPrefix('en', $dc);
    }

    public function testIgnoresPagesWithoutAliasWhenValidatingUrlPrefix(): void
    {
        $framework = $this->mockFrameworkWithPages(
            [
                'dns' => '',
                'urlPrefix' => 'de',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 1,
                'pid' => 0,
                'type' => 'root',
                'alias' => 'root',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 2,
                'pid' => 1,
                'alias' => 'foo',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 3,
                'pid' => 1,
                'alias' => '',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 4,
                'pid' => 3,
                'alias' => 'foo/bar',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 5,
                'pid' => 0,
                'type' => 'root',
                'urlPrefix' => 'de',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 6,
                'pid' => 5,
                'alias' => '',
                'urlPrefix' => 'de',
                'urlSuffix' => '.html',
            ]
        );

        $connection = $this->mockConnection(
            [['urlPrefix' => 'de', 'urlSuffix' => '.html']],
            [2, 3, 4],
            [0 => 'foo', 2 => 'foo/bar'],
            [[], [], [6]],
            true
        );

        $listener = new PageUrlListener(
            $framework,
            $this->createMock(Slug::class),
            $this->createMock(TranslatorInterface::class),
            $connection
        );

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => 1,
                'activeRecord' => (object) ['type' => 'root', 'dns' => '', 'urlPrefix' => 'de', 'urlSuffix' => ''],
            ]
        );

        $listener->validateUrlPrefix('en', $dc);
    }

    public function testDoesNotValidateTheUrlPrefixIfPageTypeIsNotRoot(): void
    {
        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->never())
            ->method('findByPk')
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);

        $listener = new PageUrlListener(
            $framework,
            $this->createMock(Slug::class),
            $this->createMock(TranslatorInterface::class),
            $this->mockConnectionWithStatement()
        );

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => 1,
                'activeRecord' => (object) [
                    'type' => 'regular',
                    'urlPrefix' => 'en',
                ],
            ]
        );

        $listener->validateUrlPrefix('de/ch', $dc);
    }

    public function testDoesNotValidateTheUrlPrefixIfTheValueHasNotChanged(): void
    {
        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->never())
            ->method('findByPk')
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);

        $listener = new PageUrlListener(
            $framework,
            $this->createMock(Slug::class),
            $this->createMock(TranslatorInterface::class),
            $this->mockConnectionWithStatement()
        );

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => 1,
                'activeRecord' => (object) [
                    'type' => 'root',
                    'urlPrefix' => 'de/ch',
                ],
            ]
        );

        $listener->validateUrlPrefix('de/ch', $dc);
    }

    public function testDoesNotValidateTheUrlPrefixIfTheRootPageIsNotFound(): void
    {
        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(1)
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);

        $listener = new PageUrlListener(
            $framework,
            $this->createMock(Slug::class),
            $this->createMock(TranslatorInterface::class),
            $this->mockConnectionWithStatement()
        );

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => 1,
                'activeRecord' => (object) [
                    'type' => 'root',
                    'dns' => '',
                    'urlPrefix' => 'en',
                ],
            ]
        );

        $listener->validateUrlPrefix('de/ch', $dc);
    }

    public function testReturnsValueWhenValidatingUrlSuffix(): void
    {
        $framework = $this->mockFrameworkWithPages(
            [
                'dns' => '',
                'urlPrefix' => 'de',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 1,
                'pid' => 0,
                'type' => 'root',
                'alias' => 'root',
                'urlPrefix' => '',
                'urlSuffix' => '',
            ],
            [
                'id' => 2,
                'pid' => 1,
                'alias' => 'foo',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ]
        );

        $connection = $this->mockConnection(
            [['urlPrefix' => 'de', 'urlSuffix' => '.html']],
            [2],
            ['foo'],
            [[]]
        );

        $listener = new PageUrlListener(
            $framework,
            $this->createMock(Slug::class),
            $this->mockTranslator(),
            $connection
        );

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => 1,
                'activeRecord' => (object) ['type' => 'root', 'urlPrefix' => 'de', 'urlSuffix' => ''],
            ]
        );

        $listener->validateUrlSuffix('.html', $dc);
    }

    public function testThrowsExceptionOnDuplicateUrlSuffix(): void
    {
        $framework = $this->mockFrameworkWithPages(
            [
                'dns' => '',
                'urlPrefix' => 'de',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 1,
                'pid' => 0,
                'type' => 'root',
                'alias' => 'root',
                'urlPrefix' => '',
                'urlSuffix' => '',
            ],
            [
                'id' => 2,
                'pid' => 1,
                'alias' => 'foo',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 3,
                'pid' => 1,
                'alias' => 'bar',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 4,
                'pid' => 3,
                'alias' => 'bar/foo',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 5,
                'pid' => 0,
                'type' => 'root',
                'urlPrefix' => 'de',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 6,
                'pid' => 5,
                'alias' => 'bar/foo',
                'urlPrefix' => 'de',
                'urlSuffix' => '.html',
            ]
        );

        $translator = $this->mockTranslator('ERR.pageUrlSuffix', '/de/bar/foo.html');

        $connection = $this->mockConnection(
            [['urlPrefix' => 'de', 'urlSuffix' => '.html']],
            [2, 3, 4],
            ['foo', 'bar', 'bar/foo'],
            [[], [], [6]]
        );

        $listener = new PageUrlListener(
            $framework,
            $this->createMock(Slug::class),
            $translator,
            $connection
        );

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => 1,
                'activeRecord' => (object) ['type' => 'root', 'urlPrefix' => 'de', 'urlSuffix' => ''],
            ]
        );

        $listener->validateUrlSuffix('.html', $dc);
    }

    public function testDoesNotValidateTheUrlSuffixIfPageTypeIsNotRoot(): void
    {
        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->never())
            ->method('findByPk')
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);

        $listener = new PageUrlListener(
            $framework,
            $this->createMock(Slug::class),
            $this->createMock(TranslatorInterface::class),
            $this->mockConnectionWithStatement()
        );

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => 1,
                'activeRecord' => (object) [
                    'type' => 'regular',
                    'urlSuffix' => '/',
                ],
            ]
        );

        $listener->validateUrlPrefix('.html', $dc);
    }

    public function testDoesNotValidateTheUrlSuffixIfTheValueHasNotChanged(): void
    {
        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->never())
            ->method('findByPk')
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);

        $listener = new PageUrlListener(
            $framework,
            $this->createMock(Slug::class),
            $this->createMock(TranslatorInterface::class),
            $this->mockConnectionWithStatement()
        );

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => 1,
                'activeRecord' => (object) [
                    'type' => 'root',
                    'urlPrefix' => '.html',
                ],
            ]
        );

        $listener->validateUrlPrefix('.html', $dc);
    }

    public function testDoesNotValidateTheUrlSuffixIfTheRootPageIsNotFound(): void
    {
        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(1)
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);

        $listener = new PageUrlListener(
            $framework,
            $this->createMock(Slug::class),
            $this->createMock(TranslatorInterface::class),
            $this->mockConnectionWithStatement()
        );

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => 1,
                'activeRecord' => (object) [
                    'type' => 'root',
                    'dns' => '',
                    'urlPrefix' => '',
                ],
            ]
        );

        $listener->validateUrlPrefix('.html', $dc);
    }

    /**
     * @return Connection&MockObject
     */
    private function mockConnection(array $prefixAndSuffix, array $ids, array $aliases, array $aliasIds, bool $prefixCheck = false): Connection
    {
        $args = [];
        $statements = [];

        if ($prefixCheck) {
            $args[] = ["SELECT COUNT(*) FROM tl_page WHERE urlPrefix=:urlPrefix AND dns=:dns AND id!=:rootId AND type='root'"];

            $statement = $this->createMock(Statement::class);
            $statement
                ->expects($this->once())
                ->method('fetchColumn')
                ->willReturn(0)
            ;

            $statements[] = $statement;
        }

        $args[] = ["SELECT urlPrefix, urlSuffix FROM tl_page WHERE type='root'"];

        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->once())
            ->method('fetchAll')
            ->willReturn($prefixAndSuffix)
        ;

        $statements[] = $statement;

        foreach ($ids as $k => $id) {
            if (!isset($aliases[$k])) {
                continue;
            }

            $args[] = [
                'SELECT id FROM tl_page WHERE alias LIKE :alias AND id!=:id',
                [
                    'alias' => '%'.$aliases[$k].'%',
                    'id' => $id,
                ],
            ];

            $statement = $this->createMock(Statement::class);
            $statement
                ->expects($this->once())
                ->method('fetchAll')
                ->with(FetchMode::COLUMN)
                ->willReturn($aliasIds[$k])
            ;

            $statements[] = $statement;
        }

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(\count($statements)))
            ->method('executeQuery')
            ->withConsecutive(...$args)
            ->willReturnOnConsecutiveCalls(...$statements)
        ;

        return $connection;
    }

    /**
     * @return ContaoFramework&MockObject
     */
    private function mockFrameworkWithPages(array $inputData, array ...$data): ContaoFramework
    {
        $pagesById = [];
        $pagesByPid = [];

        foreach ($data as $row) {
            $page = $this->mockClassWithProperties(PageModel::class, $row);
            $pagesById[$row['id']] = $page;

            if (isset($row['pid'])) {
                $pagesByPid[$row['pid']][] = $page;
            }
        }

        $pageAdapter = $this->mockAdapter(['findByPk', 'findWithDetails', 'findByPid']);
        $pageAdapter
            ->method('findByPk')
            ->willReturnCallback(
                static function (int $id) use ($pagesById) {
                    return $pagesById[$id] ?? null;
                }
            )
        ;

        $pageAdapter
            ->method('findWithDetails')
            ->willReturnCallback(
                static function (int $id) use ($pagesById) {
                    return $pagesById[$id] ?? null;
                }
            )
        ;

        $pageAdapter
            ->method('findByPid')
            ->willReturnCallback(
                static function (int $pid) use ($pagesByPid) {
                    return $pagesByPid[$pid] ?? null;
                }
            )
        ;

        $inputAdapter = $this->mockAdapter(['post']);
        $inputAdapter
            ->method('post')
            ->willReturnCallback(
                static function ($key) use ($inputData) {
                    return $inputData[$key] ?? null;
                }
            )
        ;

        return $this->mockContaoFramework(
            [
                PageModel::class => $pageAdapter,
                Input::class => $inputAdapter,
            ]
        );
    }

    /**
     * @return TranslatorInterface&MockObject
     */
    private function mockTranslator(string $messageKey = null, string $argument = null): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);

        if (null === $messageKey || null === $argument) {
            $translator
                ->expects($this->never())
                ->method('trans')
            ;

            return $translator;
        }

        $translator
            ->expects($this->once())
            ->method('trans')
            ->with($messageKey, [$argument], 'contao_default')
            ->willReturn($argument)
        ;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($argument);

        return $translator;
    }

    /**
     * @return Connection&MockObject
     */
    private function mockConnectionWithStatement(): Connection
    {
        $statement = $this->createMock(Statement::class);
        $statement
            ->method('fetchAll')
            ->willReturn([])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('executeQuery')
            ->willReturn($statement)
        ;

        return $connection;
    }
}
