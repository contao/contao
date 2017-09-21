<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Controller;

use Contao\CoreBundle\Controller\BackendCsvImportController;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class BackendCsvImportControllerTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $GLOBALS['TL_LANG']['MSC']['source'] = 'Source';
        $GLOBALS['TL_LANG']['MSC']['separator'] = 'Separator';
        $GLOBALS['TL_LANG']['MSC']['comma'] = 'Comma';
        $GLOBALS['TL_LANG']['MSC']['semicolon'] = 'Semicolon';
        $GLOBALS['TL_LANG']['MSC']['tabulator'] = 'Tabulator';
        $GLOBALS['TL_LANG']['MSC']['linebreak'] = 'Line break';
        $GLOBALS['TL_LANG']['MSC']['apply'] = 'Apply';
        $GLOBALS['TL_LANG']['MSC']['backBT'] = 'Back';
        $GLOBALS['TL_LANG']['MSC']['backBTTitle'] = 'Go back';
        $GLOBALS['TL_LANG']['MSC']['lw_import'] = ['Import'];
        $GLOBALS['TL_LANG']['MSC']['tw_import'] = ['Import'];
        $GLOBALS['TL_LANG']['MSC']['ow_import'] = ['Import'];
    }

    /**
     * {@inheritdoc}
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        unset($GLOBALS['TL_LANG']);
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\CoreBundle\Controller\BackendCsvImportController', $this->mockController());
    }

    public function testRendersTheListWizardMarkup(): void
    {
        $dc = $this->createMock(DataContainer::class);

        $dc
            ->method('__get')
            ->willReturnCallback(
                function (string $key) {
                    switch ($key) {
                        case 'id':
                            return 1;

                        case 'table':
                            return 'tl_content';

                        default:
                            return null;
                    }
                }
            )
        ;

        $expect = <<<'EOF'
<form id="tl_csv_import_lw">
  <div class="uploader"></div>
</form>

EOF;

        $request = new Request();
        $request->query->set('key', 'lw');

        $this->assertSame($expect, $this->mockController($request)->importListWizard($dc)->getContent());
    }

    public function testImportsTheListWizardData(): void
    {
        $dc = $this->createMock(DataContainer::class);

        $dc
            ->method('__get')
            ->willReturnCallback(
                function (string $key) {
                    switch ($key) {
                        case 'id':
                            return 1;

                        case 'table':
                            return 'tl_content';

                        default:
                            return null;
                    }
                }
            )
        ;

        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->atLeastOnce())
            ->method('update')
            ->with('tl_content', ['listitems' => serialize(['foo', 'bar'])], ['id' => 1])
        ;

        $request = new Request();
        $request->query->set('key', 'lw');
        $request->request->set('FORM_SUBMIT', 'tl_csv_import_lw');
        $request->request->set('separator', 'comma');
        $request->server->set('REQUEST_URI', 'http://localhost/contao');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $controller = new BackendCsvImportController(
            $this->mockContaoFramework(),
            $connection,
            $requestStack,
            $this->getRootDir()
        );

        $response = $controller->importListWizard($dc);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testRendersTheTableWizardMarkup(): void
    {
        $dc = $this->createMock(DataContainer::class);

        $dc
            ->method('__get')
            ->willReturnCallback(
                function (string $key) {
                    switch ($key) {
                        case 'id':
                            return 1;

                        case 'table':
                            return 'tl_content';

                        default:
                            return null;
                    }
                }
            )
        ;

        $expect = <<<'EOF'
<form id="tl_csv_import_tw">
  <div class="uploader"></div>
</form>

EOF;

        $request = new Request();
        $request->query->set('key', 'tw');

        $this->assertSame($expect, $this->mockController($request)->importTableWizard($dc)->getContent());
    }

    public function testImportsTheTableWizardData(): void
    {
        $dc = $this->createMock(DataContainer::class);

        $dc
            ->method('__get')
            ->willReturnCallback(
                function (string $key) {
                    switch ($key) {
                        case 'id':
                            return 1;

                        case 'table':
                            return 'tl_content';

                        default:
                            return null;
                    }
                }
            )
        ;

        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->atLeastOnce())
            ->method('update')
            ->with('tl_content', ['tableitems' => serialize([['foo', 'bar']])], ['id' => 1])
        ;

        $request = new Request();
        $request->query->set('key', 'tw');
        $request->request->set('FORM_SUBMIT', 'tl_csv_import_tw');
        $request->request->set('separator', 'comma');
        $request->server->set('REQUEST_URI', 'http://localhost/contao');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $controller = new BackendCsvImportController(
            $this->mockContaoFramework(),
            $connection,
            $requestStack,
            $this->getRootDir()
        );

        $response = $controller->importTableWizard($dc);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testRendersTheOptionWizardMarkup(): void
    {
        $dc = $this->createMock(DataContainer::class);

        $dc
            ->method('__get')
            ->willReturnCallback(
                function (string $key) {
                    switch ($key) {
                        case 'id':
                            return 1;

                        case 'table':
                            return 'tl_content';

                        default:
                            return null;
                    }
                }
            )
        ;

        $expect = <<<'EOF'
<form id="tl_csv_import_ow">
  <div class="uploader"></div>
</form>

EOF;

        $request = new Request();
        $request->query->set('key', 'ow');

        $this->assertSame($expect, $this->mockController($request)->importOptionWizard($dc)->getContent());
    }

    public function testImportsTheOptionWizardData(): void
    {
        $dc = $this->createMock(DataContainer::class);

        $dc
            ->method('__get')
            ->willReturnCallback(
                function (string $key) {
                    switch ($key) {
                        case 'id':
                            return 1;

                        case 'table':
                            return 'tl_content';

                        default:
                            return null;
                    }
                }
            )
        ;

        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->atLeastOnce())
            ->method('update')
            ->with(
                'tl_content',
                ['options' => serialize([['value' => 'foo', 'label' => 'bar', 'default' => '', 'group' => '']])],
                ['id' => 1]
            )
        ;

        $request = new Request();
        $request->query->set('key', 'ow');
        $request->request->set('FORM_SUBMIT', 'tl_csv_import_ow');
        $request->request->set('separator', 'comma');
        $request->server->set('REQUEST_URI', 'http://localhost/contao');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $controller = new BackendCsvImportController(
            $this->mockContaoFramework(),
            $connection,
            $requestStack,
            $this->getRootDir()
        );

        $response = $controller->importOptionWizard($dc);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testRedirectsIfThePostDataIsIncomplete(): void
    {
        $dc = $this->createMock(DataContainer::class);

        $dc
            ->method('__get')
            ->willReturnCallback(
                function (string $key) {
                    switch ($key) {
                        case 'id':
                            return 1;

                        case 'table':
                            return 'tl_content';

                        default:
                            return null;
                    }
                }
            )
        ;

        $request = new Request();
        $request->query->set('key', 'lw');
        $request->request->set('FORM_SUBMIT', 'tl_csv_import_lw');

        $response = $this->mockController($request)->importListWizard($dc);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(303, $response->getStatusCode());
    }

    public function testFailsIfThereIsNoRequestObject(): void
    {
        $dc = $this->createMock(DataContainer::class);

        $dc
            ->method('__get')
            ->willReturnCallback(
                function (string $key) {
                    switch ($key) {
                        case 'id':
                            return 1;

                        case 'table':
                            return 'tl_content';

                        default:
                            return null;
                    }
                }
            )
        ;

        $connection = $this->createMock(Connection::class);

        $controller = new BackendCsvImportController(
            $this->mockContaoFramework(),
            $connection,
            new RequestStack(),
            $this->getRootDir()
        );

        $this->expectException(InternalServerErrorException::class);

        $controller->importListWizard($dc);
    }

    /**
     * Mocks a controller.
     *
     * @param Request|null $request
     *
     * @return BackendCsvImportController
     */
    private function mockController(Request $request = null): BackendCsvImportController
    {
        if (null === $request) {
            $request = new Request();
        }

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $controller = new BackendCsvImportController(
            $this->mockContaoFramework(),
            $this->createMock(Connection::class),
            $requestStack,
            $this->getRootDir()
        );

        return $controller;
    }
}
