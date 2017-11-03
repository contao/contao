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

use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Controller\BackendCsvImportController;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class BackendCsvImportControllerTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        \define('TL_MODE', 'BE');
        \define('TL_ROOT', $this->getFixturesDir());

        $finder = new ResourceFinder($this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao');

        $container = $this->mockContainer();
        $container->set('session', new Session(new MockArraySessionStorage()));
        $container->set('contao.resource_finder', $finder);

        System::setContainer($container);
    }

    public function testCanBeInstantiated(): void
    {
        $controller = $this->mockController();

        $this->assertInstanceOf('Contao\CoreBundle\Controller\BackendCsvImportController', $controller);
    }

    public function testRendersTheListWizardMarkup(): void
    {
        $request = new Request();
        $request->query->set('key', 'lw');

        $html = $this
            ->mockController($request)
            ->importListWizard($this->mockDataContainer())
            ->getContent()
        ;

        $expect = <<<'EOF'
<form id="tl_csv_import_lw">
  <div class="uploader"></div>
</form>

EOF;

        $this->assertSame($expect, $html);
    }

    public function testImportsTheListWizardData(): void
    {
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
            $this->createMock(TranslatorInterface::class),
            $this->getFixturesDir()
        );

        $response = $controller->importListWizard($this->mockDataContainer());

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testRendersTheTableWizardMarkup(): void
    {
        $request = new Request();
        $request->query->set('key', 'tw');

        $html = $this
            ->mockController($request)
            ->importTableWizard($this->mockDataContainer())
            ->getContent()
        ;

        $expect = <<<'EOF'
<form id="tl_csv_import_tw">
  <div class="uploader"></div>
</form>

EOF;

        $this->assertSame($expect, $html);
    }

    public function testImportsTheTableWizardData(): void
    {
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
            $this->createMock(TranslatorInterface::class),
            $this->getFixturesDir()
        );

        $response = $controller->importTableWizard($this->mockDataContainer());

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testRendersTheOptionWizardMarkup(): void
    {
        $request = new Request();
        $request->query->set('key', 'ow');

        $html = $this
            ->mockController($request)
            ->importOptionWizard($this->mockDataContainer())
            ->getContent()
        ;

        $expect = <<<'EOF'
<form id="tl_csv_import_ow">
  <div class="uploader"></div>
</form>

EOF;

        $this->assertSame($expect, $html);
    }

    public function testImportsTheOptionWizardData(): void
    {
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
            $this->createMock(TranslatorInterface::class),
            $this->getFixturesDir()
        );

        $response = $controller->importOptionWizard($this->mockDataContainer());

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testRedirectsIfThePostDataIsIncomplete(): void
    {
        $request = new Request();
        $request->query->set('key', 'lw');
        $request->request->set('FORM_SUBMIT', 'tl_csv_import_lw');

        $response = $this
            ->mockController($request)
            ->importListWizard($this->mockDataContainer())
        ;

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame(303, $response->getStatusCode());
    }

    public function testFailsIfThereIsNoRequestObject(): void
    {
        $connection = $this->createMock(Connection::class);

        $controller = new BackendCsvImportController(
            $this->mockContaoFramework(),
            $connection,
            new RequestStack(),
            $this->createMock(TranslatorInterface::class),
            $this->getFixturesDir()
        );

        $this->expectException(InternalServerErrorException::class);

        $controller->importListWizard($this->mockDataContainer());
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
        $requestStack = new RequestStack();
        $requestStack->push($request ?: new Request());

        $translator = $this->createMock(TranslatorInterface::class);

        $translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $controller = new BackendCsvImportController(
            $this->mockContaoFramework(),
            $this->createMock(Connection::class),
            $requestStack,
            $translator,
            $this->getFixturesDir()
        );

        return $controller;
    }

    /**
     * Mocks a data container.
     *
     * @return DataContainer|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockDataContainer(): DataContainer
    {
        $properties = [
            'id' => 1,
            'table' => 'tl_content',
        ];

        return $this->mockClassWithProperties(DataContainer::class, $properties);
    }
}
