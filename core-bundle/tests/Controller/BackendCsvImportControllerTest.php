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
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\FileUpload;
use Contao\Message;
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
            ->importListWizardAction($this->mockDataContainer())
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
            $this->mockFramework(['files/data.csv']),
            $connection,
            $requestStack,
            $this->createMock(TranslatorInterface::class),
            $this->getFixturesDir()
        );

        $response = $controller->importListWizardAction($this->mockDataContainer());

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testRendersTheTableWizardMarkup(): void
    {
        $request = new Request();
        $request->query->set('key', 'tw');

        $html = $this
            ->mockController($request)
            ->importTableWizardAction($this->mockDataContainer())
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
            $this->mockFramework(['files/data.csv']),
            $connection,
            $requestStack,
            $this->createMock(TranslatorInterface::class),
            $this->getFixturesDir()
        );

        $response = $controller->importTableWizardAction($this->mockDataContainer());

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testRendersTheOptionWizardMarkup(): void
    {
        $request = new Request();
        $request->query->set('key', 'ow');

        $html = $this
            ->mockController($request)
            ->importOptionWizardAction($this->mockDataContainer())
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
            $this->mockFramework(['files/data.csv']),
            $connection,
            $requestStack,
            $this->createMock(TranslatorInterface::class),
            $this->getFixturesDir()
        );

        $response = $controller->importOptionWizardAction($this->mockDataContainer());

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testRedirectsIfThePostDataIsIncomplete(): void
    {
        $connection = $this->createMock(Connection::class);

        $request = new Request();
        $request->query->set('key', 'lw');
        $request->request->set('FORM_SUBMIT', 'tl_csv_import_lw');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $translator = $this->createMock(TranslatorInterface::class);

        $translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $controller = new BackendCsvImportController(
            $this->mockFramework(['files/data.csv'], true),
            $connection,
            $requestStack,
            $translator,
            $this->getFixturesDir()
        );

        $response = $controller->importListWizardAction($this->mockDataContainer());

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame(303, $response->getStatusCode());
    }

    public function testFailsIfThereIsNoRequestObject(): void
    {
        $connection = $this->createMock(Connection::class);

        $controller = new BackendCsvImportController(
            $this->mockFramework(),
            $connection,
            new RequestStack(),
            $this->createMock(TranslatorInterface::class),
            $this->getFixturesDir()
        );

        $this->expectException(InternalServerErrorException::class);

        $controller->importListWizardAction($this->mockDataContainer());
    }

    public function testFailsIfThereAreNoFiles(): void
    {
        $connection = $this->createMock(Connection::class);

        $request = new Request();
        $request->query->set('key', 'lw');
        $request->request->set('FORM_SUBMIT', 'tl_csv_import_lw');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $translator = $this->createMock(TranslatorInterface::class);

        $translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $controller = new BackendCsvImportController(
            $this->mockFramework([], true),
            $connection,
            $requestStack,
            $translator,
            $this->getFixturesDir()
        );

        $response = $controller->importListWizardAction($this->mockDataContainer());

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame(303, $response->getStatusCode());
    }

    public function testFailsIfTheFileExtensionIsNotCsv(): void
    {
        $connection = $this->createMock(Connection::class);

        $request = new Request();
        $request->query->set('key', 'lw');
        $request->request->set('FORM_SUBMIT', 'tl_csv_import_lw');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $translator = $this->createMock(TranslatorInterface::class);

        $translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $controller = new BackendCsvImportController(
            $this->mockFramework(['files/data.jpg'], true),
            $connection,
            $requestStack,
            $translator,
            $this->getFixturesDir()
        );

        $response = $controller->importListWizardAction($this->mockDataContainer());

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame(303, $response->getStatusCode());
    }

    /**
     * Mocks the Contao framework.
     *
     * @param array $files
     * @param bool  $expectError
     *
     * @return ContaoFrameworkInterface
     */
    private function mockFramework(array $files = [], bool $expectError = false): ContaoFrameworkInterface
    {
        $uploader = $this->createMock(FileUpload::class);

        $uploader
            ->method('uploadTo')
            ->willReturn($files)
        ;

        $adapter = $this->mockAdapter(['addError']);

        $adapter
            ->expects($expectError ? $this->once() : $this->never())
            ->method('addError')
        ;

        $framework = $this->mockContaoFramework([Message::class => $adapter]);

        $framework
            ->method('createInstance')
            ->willReturn($uploader)
        ;

        return $framework;
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
            $this->mockFramework(),
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
