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

use Contao\CoreBundle\EventListener\DataContainer\PageUrlListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Slug\Slug;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\Input;
use Contao\PageModel;
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
            $this->createMock(Connection::class)
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

        $languagePrefix = $activeRecord['languagePrefix'] ? $activeRecord['languagePrefix'].'/' : '';
        $url = '/'.$languagePrefix.$value.$activeRecord['urlSuffix'];
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
                'languagePrefix' => '',
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
                'languagePrefix' => '',
                'urlSuffix' => '',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => false,
                'domain' => '',
                'languagePrefix' => '',
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
                'languagePrefix' => 'de',
                'urlSuffix' => '',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => false,
                'domain' => '',
                'languagePrefix' => 'de',
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
                'languagePrefix' => '',
                'urlSuffix' => '.html',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => false,
                'domain' => '',
                'languagePrefix' => '',
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
                'languagePrefix' => 'de',
                'urlSuffix' => '.html',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 1,
                'useFolderUrl' => false,
                'domain' => '',
                'languagePrefix' => 'de',
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
                'languagePrefix' => '',
                'urlSuffix' => '',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 2,
                'useFolderUrl' => false,
                'domain' => '',
                'languagePrefix' => '',
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
                'languagePrefix' => 'de',
                'urlSuffix' => '',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 2,
                'useFolderUrl' => false,
                'domain' => '',
                'languagePrefix' => 'fr',
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
                'languagePrefix' => '',
                'urlSuffix' => '',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 2,
                'useFolderUrl' => false,
                'domain' => 'example.com',
                'languagePrefix' => '',
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
                'languagePrefix' => '',
                'urlSuffix' => '.html',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 2,
                'useFolderUrl' => false,
                'domain' => '',
                'languagePrefix' => '',
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
                'languagePrefix' => 'de',
                'urlSuffix' => '.html',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 2,
                'useFolderUrl' => false,
                'domain' => '',
                'languagePrefix' => 'de',
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
                'languagePrefix' => 'de/ch',
                'urlSuffix' => '.html',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'ch/foo',
                'rootId' => 2,
                'useFolderUrl' => false,
                'domain' => '',
                'languagePrefix' => 'de',
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
                'languagePrefix' => 'de',
                'urlSuffix' => '.html',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 2,
                'useFolderUrl' => false,
                'domain' => '',
                'languagePrefix' => 'de/ch',
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
                'languagePrefix' => '',
                'urlSuffix' => '.html',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo.ht',
                'rootId' => 2,
                'useFolderUrl' => false,
                'domain' => '',
                'languagePrefix' => '',
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
                'languagePrefix' => '',
                'urlSuffix' => 'ml',
            ],
            [[
                'id' => 2,
                'title' => 'Foo',
                'alias' => 'foo',
                'rootId' => 2,
                'useFolderUrl' => false,
                'domain' => '',
                'languagePrefix' => '',
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
                'languagePrefix' => '',
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
                'languagePrefix' => '',
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
                'languagePrefix' => '',
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
                'languagePrefix' => '',
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
                'languagePrefix' => 'ch/de',
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
                'languagePrefix' => 'ch',
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
                'languagePrefix' => '',
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
                'languagePrefix' => '',
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
            ->expects($this->exactly(2))
            ->method('execute')
            ->with(['pageId' => 17])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(2))
            ->method('prepare')
            ->withConsecutive(
                ['DELETE FROM tl_search_index WHERE pid IN (SELECT id FROM tl_search WHERE pid=:pageId)'],
                ['DELETE FROM tl_search WHERE pid=:pageId']
            )
            ->willReturn($statement)
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
            $this->createMock(ContaoFramework::class),
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
            ->method('prepare')
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
            $this->createMock(ContaoFramework::class),
            $this->createMock(Slug::class),
            $this->createMock(TranslatorInterface::class),
            $connection
        );

        $listener->purgeSearchIndexOnAliasChange('foo', $dc);
    }

    public function testResetsThePrefixesAndSuffixes(): void
    {
        $framework = $this->mockFrameworkWithPages(
            [],
            [
                'id' => 1,
                'alias' => 'foo',
                'domain' => '',
                'languagePrefix' => '',
                'urlSuffix' => '',
            ],
            [
                'id' => 2,
                'alias' => 'bar',
                'domain' => '',
                'languagePrefix' => '',
                'urlSuffix' => '',
            ],
            [
                'id' => 3,
                'alias' => 'baz',
                'domain' => '',
                'languagePrefix' => '',
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
                ["SELECT languagePrefix, urlSuffix FROM tl_page WHERE type='root'"],
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
                ["SELECT languagePrefix, urlSuffix FROM tl_page WHERE type='root'"],
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

    public function testReturnsValueWhenValidatingLanguagePrefix(): void
    {
        $framework = $this->mockFrameworkWithPages(
            [
                'dns' => '',
                'languagePrefix' => 'de',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 1,
                'pid' => 0,
                'type' => 'root',
                'alias' => 'root',
                'languagePrefix' => '',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 2,
                'pid' => 1,
                'alias' => 'foo',
                'languagePrefix' => '',
                'urlSuffix' => '.html',
            ]
        );

        $connection = $this->mockConnection(
            [['languagePrefix' => 'de', 'urlSuffix' => '.html']],
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
                'activeRecord' => (object) ['type' => 'root', 'languagePrefix' => 'de', 'urlSuffix' => ''],
            ]
        );

        $listener->validateLanguagePrefix('en', $dc);
    }

    public function testThrowsExceptionOnDuplicateLanguagePrefix(): void
    {
        $framework = $this->mockFrameworkWithPages(
            [
                'dns' => '',
                'languagePrefix' => 'de',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 1,
                'pid' => 0,
                'type' => 'root',
                'alias' => 'root',
                'languagePrefix' => '',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 2,
                'pid' => 1,
                'alias' => 'foo',
                'languagePrefix' => '',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 3,
                'pid' => 1,
                'alias' => 'bar',
                'languagePrefix' => '',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 4,
                'pid' => 3,
                'alias' => 'bar/foo',
                'languagePrefix' => '',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 5,
                'pid' => 0,
                'type' => 'root',
                'languagePrefix' => 'de',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 6,
                'pid' => 5,
                'alias' => 'bar/foo',
                'languagePrefix' => 'de',
                'urlSuffix' => '.html',
            ]
        );

        $translator = $this->mockTranslator('ERR.pageUrlPrefix', '/de/bar/foo.html');

        $connection = $this->mockConnection(
            [['languagePrefix' => 'de', 'urlSuffix' => '.html']],
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
                'activeRecord' => (object) ['type' => 'root', 'languagePrefix' => 'de', 'urlSuffix' => ''],
            ]
        );

        $listener->validateLanguagePrefix('en', $dc);
    }

    public function testDoesNotValidateTheLanguagePrefixIfPageTypeIsNotRoot(): void
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
            $this->createMock(Connection::class)
        );

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => 1,
                'activeRecord' => (object) [
                    'type' => 'regular',
                    'languagePrefix' => 'en',
                ],
            ]
        );

        $listener->validateLanguagePrefix('de/ch', $dc);
    }

    public function testDoesNotValidateTheLanguagePrefixIfTheValueHasNotChanged(): void
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
            $this->createMock(Connection::class)
        );

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => 1,
                'activeRecord' => (object) [
                    'type' => 'root',
                    'languagePrefix' => 'de/ch',
                ],
            ]
        );

        $listener->validateLanguagePrefix('de/ch', $dc);
    }

    public function testDoesNotValidateTheLanguagePrefixIfTheRootPageIsNotFound(): void
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
            $this->createMock(Connection::class)
        );

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => 1,
                'activeRecord' => (object) [
                    'type' => 'root',
                    'languagePrefix' => 'en',
                ],
            ]
        );

        $listener->validateLanguagePrefix('de/ch', $dc);
    }

    public function testReturnsValueWhenValidatingUrlSuffix(): void
    {
        $framework = $this->mockFrameworkWithPages(
            [
                'dns' => '',
                'languagePrefix' => 'de',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 1,
                'pid' => 0,
                'type' => 'root',
                'alias' => 'root',
                'languagePrefix' => '',
                'urlSuffix' => '',
            ],
            [
                'id' => 2,
                'pid' => 1,
                'alias' => 'foo',
                'languagePrefix' => '',
                'urlSuffix' => '.html',
            ]
        );

        $connection = $this->mockConnection(
            [['languagePrefix' => 'de', 'urlSuffix' => '.html']],
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
                'activeRecord' => (object) ['type' => 'root', 'languagePrefix' => 'de', 'urlSuffix' => ''],
            ]
        );

        $listener->validateUrlSuffix('.html', $dc);
    }

    public function testThrowsExceptionOnDuplicateUrlSuffix(): void
    {
        $framework = $this->mockFrameworkWithPages(
            [
                'dns' => '',
                'languagePrefix' => 'de',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 1,
                'pid' => 0,
                'type' => 'root',
                'alias' => 'root',
                'languagePrefix' => '',
                'urlSuffix' => '',
            ],
            [
                'id' => 2,
                'pid' => 1,
                'alias' => 'foo',
                'languagePrefix' => '',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 3,
                'pid' => 1,
                'alias' => 'bar',
                'languagePrefix' => '',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 4,
                'pid' => 3,
                'alias' => 'bar/foo',
                'languagePrefix' => '',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 5,
                'pid' => 0,
                'type' => 'root',
                'languagePrefix' => 'de',
                'urlSuffix' => '.html',
            ],
            [
                'id' => 6,
                'pid' => 5,
                'alias' => 'bar/foo',
                'languagePrefix' => 'de',
                'urlSuffix' => '.html',
            ]
        );

        $translator = $this->mockTranslator('ERR.pageUrlSuffix', '/de/bar/foo.html');

        $connection = $this->mockConnection(
            [['languagePrefix' => 'de', 'urlSuffix' => '.html']],
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
                'activeRecord' => (object) ['type' => 'root', 'languagePrefix' => 'de', 'urlSuffix' => ''],
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
            $this->createMock(Connection::class)
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

        $listener->validateLanguagePrefix('.html', $dc);
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
            $this->createMock(Connection::class)
        );

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => 1,
                'activeRecord' => (object) [
                    'type' => 'root',
                    'languagePrefix' => '.html',
                ],
            ]
        );

        $listener->validateLanguagePrefix('.html', $dc);
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
            $this->createMock(Connection::class)
        );

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            [
                'id' => 1,
                'activeRecord' => (object) [
                    'type' => 'root',
                    'languagePrefix' => '',
                ],
            ]
        );

        $listener->validateLanguagePrefix('.html', $dc);
    }

    /**
     * @return Connection&MockObject
     */
    private function mockConnection(array $prefixAndSuffix, array $ids, array $aliases, array $aliasIds): Connection
    {
        $args = [];
        $statements = [];

        $args[] = ["SELECT languagePrefix, urlSuffix FROM tl_page WHERE type='root'"];
        $statements[] = $this->createMock(Statement::class);
        $statements[0]
            ->expects($this->once())
            ->method('fetchAll')
            ->willReturn($prefixAndSuffix)
        ;

        foreach ($ids as $k => $id) {
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
    private function mockTranslator(string $messageKey = null, string $url = null): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);

        if (null === $messageKey || null === $url) {
            $translator
                ->expects($this->never())
                ->method('trans')
            ;

            return $translator;
        }

        $translator
            ->expects($this->once())
            ->method('trans')
            ->with($messageKey, [$url], 'contao_default')
            ->willReturn($url)
        ;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($url);

        return $translator;
    }
}
