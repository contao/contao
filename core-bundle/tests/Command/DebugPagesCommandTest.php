<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Command;

use Contao\Config;
use Contao\CoreBundle\Command\DebugPagesCommand;
use Contao\CoreBundle\Controller\Page\RootPageController;
use Contao\CoreBundle\Fixtures\Controller\Page\TestPageController;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\RouteConfig;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DcaExtractor;
use Contao\DcaLoader;
use Contao\PageError401;
use Contao\PageError403;
use Contao\PageError404;
use Contao\PageForward;
use Contao\PageLogout;
use Contao\PageModel;
use Contao\PageRedirect;
use Contao\PageRegular;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class DebugPagesCommandTest extends TestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANG'], $GLOBALS['TL_MIME'], $GLOBALS['TL_DCA']);

        $this->resetStaticProperties([DcaExtractor::class, DcaLoader::class, Table::class, Terminal::class, System::class, Config::class]);

        parent::tearDown();
    }

    public function testNameAndArguments(): void
    {
        $framework = $this->mockContaoFramework();
        $pageRegistry = $this->createMock(PageRegistry::class);
        $command = new DebugPagesCommand($framework, $pageRegistry);

        $this->assertSame('debug:pages', $command->getName());
        $this->assertSame(0, $command->getDefinition()->getArgumentCount());
        $this->assertEmpty($command->getDefinition()->getOptions());
    }

    /**
     * @dataProvider commandOutputProvider
     */
    public function testCommandOutput(array $pages, array $legacyPages, string $expectedOutput): void
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager
            ->method('introspectSchema')
            ->willReturn(new Schema())
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('database_connection', $connection);
        $container->setParameter('contao.resources_paths', $this->getTempDir());
        $container->setParameter('kernel.cache_dir', $this->getTempDir().'/var/cache');

        (new Filesystem())->mkdir($this->getTempDir().'/languages/en');
        (new Filesystem())->dumpFile($this->getTempDir().'/var/cache/contao/sql/tl_page.php', '<?php $GLOBALS["TL_DCA"]["tl_page"] = [];');

        System::setContainer($container);

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('keys')
            ->willReturn(array_values(array_column($pages, 0)))
        ;

        $pageRegistry
            ->method('supportsContentComposition')
            ->willReturnCallback(static fn (PageModel $pageModel): bool => 'regular' === $pageModel->type)
        ;

        $command = new DebugPagesCommand($this->mockContaoFramework(), $pageRegistry);

        $GLOBALS['TL_PTY'] = $legacyPages;

        foreach ($pages as $page) {
            $command->add($page[0], $page[1], $page[2]);
        }

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame($expectedOutput, preg_replace('/ +(?=\n)/', '', $commandTester->getDisplay(true)));

        unset($GLOBALS['TL_PTY']);
    }

    public function commandOutputProvider(): \Generator
    {
        yield 'Regular pages list' => [
            [
                ['root', new RouteConfig('foo', null, '.php', [], [], ['_controller' => RootPageController::class]), null],
            ],
            [
                'regular' => PageRegular::class,
                'forward' => PageForward::class,
                'redirect' => PageRedirect::class,
                'logout' => PageLogout::class,
                'error_401' => PageError401::class,
                'error_403' => PageError403::class,
                'error_404' => PageError404::class,
            ],
            <<<'OUTPUT'

                Contao Pages
                ============

                 ----------- ------ ------------ --------------------- ---------------- -------------- -------------------------------------------------------------------- ---------
                  Type        Path   URL Suffix   Content Composition   Route Enhancer   Requirements   Defaults                                                             Options
                 ----------- ------ ------------ --------------------- ---------------- -------------- -------------------------------------------------------------------- ---------
                  error_401   *      *            no                    -                -              -                                                                    -
                  error_403   *      *            no                    -                -              -                                                                    -
                  error_404   *      *            no                    -                -              -                                                                    -
                  forward     *      *            no                    -                -              -                                                                    -
                  logout      *      *            no                    -                -              -                                                                    -
                  redirect    *      *            no                    -                -              -                                                                    -
                  regular     *      *            yes                   -                -              -                                                                    -
                  root        foo    .php         no                    -                -              _controller : Contao\CoreBundle\Controller\Page\RootPageController   -
                 ----------- ------ ------------ --------------------- ---------------- -------------- -------------------------------------------------------------------- ---------


                OUTPUT,
        ];

        yield 'With custom pages' => [
            [
                ['root', new RouteConfig('foo', null, '.php', [], [], ['_controller' => RootPageController::class]), null],
                ['bar', new RouteConfig('foo/bar', null, '.html', [], [], ['_controller' => TestPageController::class]), null],
                ['baz', new RouteConfig(null, null, null, ['page' => '\d+'], ['utf8' => false], []), null],
            ],
            [
                'regular' => PageRegular::class,
            ],
            <<<'OUTPUT'

                Contao Pages
                ============

                 --------- --------- ------------ --------------------- ---------------- -------------- ----------------------------------------------------------------------------- --------------
                  Type      Path      URL Suffix   Content Composition   Route Enhancer   Requirements   Defaults                                                                      Options
                 --------- --------- ------------ --------------------- ---------------- -------------- ----------------------------------------------------------------------------- --------------
                  bar       foo/bar   .html        no                    -                -              _controller : Contao\CoreBundle\Fixtures\Controller\Page\TestPageController   -
                  baz       *         *            no                    -                page : \d+     -                                                                             utf8 : false
                  regular   *         *            yes                   -                -              -                                                                             -
                  root      foo       .php         no                    -                -              _controller : Contao\CoreBundle\Controller\Page\RootPageController            -
                 --------- --------- ------------ --------------------- ---------------- -------------- ----------------------------------------------------------------------------- --------------


                OUTPUT,
        ];
    }
}
