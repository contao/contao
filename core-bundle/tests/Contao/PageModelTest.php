<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\Config;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Database;
use Contao\Database\Result;
use Contao\Database\Statement;
use Contao\DcaExtractor;
use Contao\DcaLoader;
use Contao\Environment;
use Contao\Input;
use Contao\Model;
use Contao\Model\Collection;
use Contao\Model\Registry;
use Contao\PageModel;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class PageModelTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_MODELS']['tl_page'] = PageModel::class;

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager
            ->method('introspectSchema')
            ->willReturn(new Schema())
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('quoteIdentifier')
            ->willReturnArgument(0)
        ;

        $connection
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('database_connection', $connection);
        $container->set('contao.security.token_checker', $this->createMock(TokenChecker::class));
        $container->setParameter('contao.resources_paths', $this->getTempDir());
        $container->setParameter('kernel.cache_dir', $this->getTempDir().'/var/cache');

        (new Filesystem())->mkdir($this->getTempDir().'/languages/en');
        (new Filesystem())->dumpFile($this->getTempDir().'/var/cache/contao/sql/tl_page.php', '<?php $GLOBALS["TL_DCA"]["tl_page"] = [];');

        System::setContainer($container);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MODELS'], $GLOBALS['TL_LANG'], $GLOBALS['TL_MIME'], $GLOBALS['TL_DCA']);

        PageModel::reset();

        $this->resetStaticProperties([Registry::class, Model::class, DcaExtractor::class, DcaLoader::class, Database::class, Input::class, System::class, Config::class, Environment::class]);

        parent::tearDown();
    }

    public function testCreatingEmptyPageModel(): void
    {
        $pageModel = new PageModel();

        $this->assertNull($pageModel->id);
        $this->assertNull($pageModel->alias);
    }

    public function testCreatingPageModelFromArray(): void
    {
        $pageModel = new PageModel(['id' => 1, 'alias' => 'alias']);

        $this->assertSame(1, $pageModel->id);
        $this->assertSame('alias', $pageModel->alias);
    }

    public function testCreatingPageModelFromDatabaseResult(): void
    {
        $pageModel = new PageModel(new Result([['id' => 1, 'alias' => 'alias']], 'SELECT * FROM tl_page WHERE id = 1'));

        $this->assertSame(1, $pageModel->id);
        $this->assertSame('alias', $pageModel->alias);
    }

    public function testFindByPk(): void
    {
        $statement = $this->createMock(Statement::class);
        $statement
            ->method('execute')
            ->willReturn(new Result([['id' => 1, 'alias' => 'alias']], ''))
        ;

        $database = $this->createMock(Database::class);
        $database
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($statement)
        ;

        $this->mockDatabase($database);

        $pageModel = PageModel::findByPk(1);

        $this->assertSame(1, $pageModel->id);
        $this->assertSame('alias', $pageModel->alias);
    }

    /**
     * @group legacy
     *
     * @dataProvider similarAliasProvider
     */
    public function testFindSimilarByAlias(array $page, string $alias, array $rootData): void
    {
        PageModel::reset();

        $database = $this->createMock(Database::class);
        $database
            ->expects($this->once())
            ->method('execute')
            ->with("SELECT urlPrefix, urlSuffix FROM tl_page WHERE type='root'")
            ->willReturn(new Result($rootData, ''))
        ;

        $aliasStatement = $this->createMock(Statement::class);
        $aliasStatement
            ->expects($this->once())
            ->method('execute')
            ->with('%'.$alias.'%', $page['id'])
            ->willReturn(new Result([['id' => 42]], ''))
        ;

        $database
            ->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM tl_page WHERE tl_page.alias LIKE ? AND tl_page.id!=?')
            ->willReturn($aliasStatement)
        ;

        $this->mockDatabase($database);

        $sourcePage = $this->mockClassWithProperties(PageModel::class, $page);
        $result = PageModel::findSimilarByAlias($sourcePage);

        $this->assertInstanceOf(Collection::class, $result);

        $pageModel = $result->current();

        $this->assertSame(42, $pageModel->id);
    }

    public function similarAliasProvider(): \Generator
    {
        yield 'Use original alias without prefix and suffix' => [
            [
                'id' => 1,
                'alias' => 'foo',
                'urlPrefix' => '',
                'urlSuffix' => '',
            ],
            'foo',
            [],
        ];

        yield 'Strips prefix' => [
            [
                'id' => 1,
                'alias' => 'de/foo',
                'urlPrefix' => '',
                'urlSuffix' => '',
            ],
            'foo',
            [
                ['urlPrefix' => 'de', 'urlSuffix' => ''],
            ],
        ];

        yield 'Strips multiple prefixes' => [
            [
                'id' => 1,
                'alias' => 'switzerland/german/foo',
                'urlPrefix' => '',
                'urlSuffix' => '',
            ],
            'foo',
            [
                ['urlPrefix' => 'switzerland', 'urlSuffix' => ''],
                ['urlPrefix' => 'switzerland/german', 'urlSuffix' => ''],
            ],
        ];

        yield 'Strips the current prefix' => [
            [
                'id' => 1,
                'alias' => 'de/foo',
                'urlPrefix' => 'de',
                'urlSuffix' => '',
            ],
            'foo',
            [
                ['urlPrefix' => 'en', 'urlSuffix' => ''],
            ],
        ];

        yield 'Strips suffix' => [
            [
                'id' => 1,
                'alias' => 'foo.html',
                'urlPrefix' => '',
                'urlSuffix' => '',
            ],
            'foo',
            [
                ['urlPrefix' => '', 'urlSuffix' => '.html'],
            ],
        ];

        yield 'Strips multiple suffixes' => [
            [
                'id' => 1,
                'alias' => 'foo.php',
                'urlPrefix' => '',
                'urlSuffix' => '',
            ],
            'foo',
            [
                ['urlPrefix' => '', 'urlSuffix' => '.html'],
                ['urlPrefix' => '', 'urlSuffix' => '.php'],
            ],
        ];

        yield 'Strips the current suffix' => [
            [
                'id' => 1,
                'alias' => 'foo.html',
                'urlPrefix' => '',
                'urlSuffix' => '.html',
            ],
            'foo',
            [
                ['urlPrefix' => '', 'urlSuffix' => '.php'],
            ],
        ];
    }

    public function testDoesNotFindSimilarIfAliasIsEmpty(): void
    {
        PageModel::reset();

        $database = $this->createMock(Database::class);
        $database
            ->expects($this->never())
            ->method('execute')
        ;

        $database
            ->expects($this->never())
            ->method('execute')
        ;

        $this->mockDatabase($database);

        $sourcePage = $this->mockClassWithProperties(PageModel::class, [
            'id' => 1,
            'alias' => '',
        ]);

        $sourcePage
            ->expects($this->never())
            ->method('loadDetails')
        ;

        $result = PageModel::findSimilarByAlias($sourcePage);

        $this->assertNull($result);
    }

    /**
     * @dataProvider layoutInheritanceParentPagesProvider
     */
    public function testInheritingLayoutFromParentsInLoadDetails(array $parents, int $expectedLayout): void
    {
        $page = new PageModel();
        $page->pid = 42;

        $numberOfParents = \count($parents);

        $statement = $this->createMock(Statement::class);
        $statement
            ->method('execute')
            ->willReturnCallback(
                static function () use (&$parents) {
                    return $parents ? new Result(array_shift($parents), '') : new Result([], '');
                },
            )
        ;

        $database = $this->createMock(Database::class);
        $database
            ->expects($this->exactly($numberOfParents))
            ->method('prepare')
            ->willReturn($statement)
        ;

        $this->mockDatabase($database);
        $page->loadDetails();

        $this->assertSame($expectedLayout, $page->layout);
    }

    public function layoutInheritanceParentPagesProvider(): \Generator
    {
        yield 'no parent with an inheritable layout' => [
            [
                [['id' => '1', 'pid' => '2']],
                [['id' => '2', 'pid' => '3', 'includeLayout' => 0, 'layout' => 1, 'subpageLayout' => 2]],
                [['id' => '3', 'pid' => '0']],
            ],
            0,
        ];

        yield 'inherit layout from parent page' => [
            [
                [['id' => '1', 'pid' => '2']],
                [['id' => '2', 'pid' => '3', 'includeLayout' => 1, 'layout' => 1, 'subpageLayout' => 0]],
                [['id' => '3', 'pid' => '0']],
            ],
            1,
        ];

        yield 'inherit subpages layout from parent page' => [
            [
                [['id' => '1', 'pid' => '2']],
                [['id' => '2', 'pid' => '3', 'includeLayout' => 1, 'layout' => 1, 'subpageLayout' => 2]],
                [['id' => '3', 'pid' => '0']],
            ],
            2,
        ];

        yield 'multiple parents with layouts' => [
            [
                [['id' => '1', 'pid' => '2', 'includeLayout' => 0, 'layout' => 1, 'subpageLayout' => 1]],
                [['id' => '2', 'pid' => '3', 'includeLayout' => 1, 'layout' => 2, 'subpageLayout' => 3]],
                [['id' => '3', 'pid' => '0', 'includeLayout' => 1, 'layout' => 4, 'subpageLayout' => 0]],
            ],
            3,
        ];
    }

    /**
     * @group legacy
     *
     * @runInSeparateProcess
     *
     * @dataProvider folderUrlProvider
     */
    public function testFolderUrlInheritsTheParentAlias(array $databaseResultData, string $expectedFolderUrl): void
    {
        if (!\defined('TL_MODE')) {
            \define('TL_MODE', 'BE');
        }

        $statement = $this->createMock(Statement::class);
        $statement
            ->method('execute')
            ->willReturnOnConsecutiveCalls(...array_map(static fn ($p) => new Result([$p], ''), $databaseResultData))
        ;

        $database = $this->createMock(Database::class);
        $database
            ->expects($this->exactly(\count($databaseResultData)))
            ->method('prepare')
            ->willReturn($statement)
        ;

        $this->mockDatabase($database);

        $page = PageModel::findWithDetails(3);

        $this->assertInstanceOf(PageModel::class, $page);
        $this->assertSame($expectedFolderUrl, $page->folderUrl);
    }

    public function folderUrlProvider(): \Generator
    {
        yield 'Inherits the alias from parent page' => [
            [
                ['id' => '3', 'pid' => '2', 'alias' => 'alias3'],
                ['id' => '2', 'pid' => '1', 'alias' => 'alias2'],
                ['id' => '1', 'pid' => '0', 'alias' => 'alias1'],
            ],
            'alias2/',
        ];

        yield 'Inherits a folderUrl from parent page' => [
            [
                ['id' => '3', 'pid' => '2', 'alias' => 'baz'],
                ['id' => '2', 'pid' => '1', 'alias' => 'foo/bar'],
                ['id' => '1', 'pid' => '0', 'alias' => 'alias1'],
            ],
            'foo/bar/',
        ];

        yield 'Does not inherit from the root page' => [
            [
                ['id' => '2', 'pid' => '1', 'alias' => 'baz'],
                ['id' => '1', 'pid' => '0', 'type' => 'root', 'fallback' => '1', 'alias' => 'foo/bar'],
            ],
            '',
        ];

        yield 'Does not inherit the index alias' => [
            [
                ['id' => '2', 'pid' => '1', 'alias' => 'baz'],
                ['id' => '1', 'pid' => '0', 'alias' => 'index'],
            ],
            '',
        ];
    }

    public function testUsesAbsolutePathReferenceForFrontendUrl(): void
    {
        $page = new PageModel();
        $page->pid = 42;
        $page->domain = 'example.com';

        $context = RequestContext::fromUri('https://example.com');

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with(PageRoute::PAGE_BASED_ROUTE_NAME, [RouteObjectInterface::CONTENT_OBJECT => $page, 'parameters' => null], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/page')
        ;

        $router
            ->expects($this->once())
            ->method('getContext')
            ->willReturn($context)
        ;

        System::getContainer()->set('router', $router);

        $this->assertSame('/page', $page->getFrontendUrl());
    }

    public function testUsesAbsoluteUrlReferenceForFrontendUrlOnOtherDomain(): void
    {
        $page = new PageModel();
        $page->pid = 42;
        $page->domain = 'foobar.com';

        $context = RequestContext::fromUri('https://example.com');

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with(PageRoute::PAGE_BASED_ROUTE_NAME, [RouteObjectInterface::CONTENT_OBJECT => $page, 'parameters' => null], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://foobar.com/page')
        ;

        $router
            ->expects($this->once())
            ->method('getContext')
            ->willReturn($context)
        ;

        System::getContainer()->set('router', $router);

        $this->assertSame('https://foobar.com/page', $page->getFrontendUrl());
    }

    public function testUsesAbsoluteUrlReferenceForAbsoluteUrl(): void
    {
        $page = new PageModel();
        $page->pid = 42;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with(PageRoute::PAGE_BASED_ROUTE_NAME, [RouteObjectInterface::CONTENT_OBJECT => $page, 'parameters' => null], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/page')
        ;

        System::getContainer()->set('router', $router);

        $this->assertSame('https://example.com/page', $page->getAbsoluteUrl());
    }

    private function mockDatabase(Database $database): void
    {
        $property = (new \ReflectionClass($database))->getProperty('objInstance');
        $property->setValue(null, $database);

        $this->assertSame($database, Database::getInstance());
    }
}
