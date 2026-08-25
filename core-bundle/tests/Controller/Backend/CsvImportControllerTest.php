<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\Backend;

use Contao\Config;
use Contao\CoreBundle\Controller\Backend\CsvImportController;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\FileUpload;
use Contao\Message;
use Contao\System;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class CsvImportControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME'], $GLOBALS['TL_TEST']);

        $this->resetStaticProperties([System::class, Config::class]);

        parent::tearDown();
    }

    public function testRendersTheListWizardMarkup(): void
    {
        $request = new Request();
        $request->query->set('key', 'lw');

        $html = $this
            ->getController($request)
            ->importListWizardAction($this->mockDataContainer())
            ->getContent()
        ;

        $this->assertSame('<content>', $html);
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

        $response = $this
            ->getController($request, $connection)
            ->importListWizardAction($this->mockDataContainer())
        ;

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testRendersTheTableWizardMarkup(): void
    {
        $request = new Request();
        $request->query->set('key', 'tw');

        $html = $this
            ->getController($request)
            ->importTableWizardAction($this->mockDataContainer())
            ->getContent()
        ;

        $this->assertSame('<content>', $html);
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

        $response = $this
            ->getController($request, $connection)
            ->importTableWizardAction($this->mockDataContainer())
        ;

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testRendersTheOptionWizardMarkup(): void
    {
        $request = new Request();
        $request->query->set('key', 'ow');

        $html = $this
            ->getController($request)
            ->importOptionWizardAction($this->mockDataContainer())
            ->getContent()
        ;

        $this->assertSame('<content>', $html);
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
                ['id' => 1],
            )
        ;

        $request = new Request();
        $request->query->set('key', 'ow');

        $request->request->set('FORM_SUBMIT', 'tl_csv_import_ow');
        $request->request->set('separator', 'comma');

        $request->server->set('REQUEST_URI', 'http://localhost/contao');

        $response = $this
            ->getController($request, $connection)
            ->importOptionWizardAction($this->mockDataContainer())
        ;

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testRedirectsIfThePostDataIsIncomplete(): void
    {
        $request = new Request();
        $request->query->set('key', 'lw');
        $request->request->set('FORM_SUBMIT', 'tl_csv_import_lw');

        $response = $this
            ->getController($request, null, $this->mockFramework(['files/data.csv'], true))
            ->importListWizardAction($this->mockDataContainer())
        ;

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testFailsIfThereIsNoRequestObject(): void
    {
        $connection = $this->createStub(Connection::class);

        $controller = new CsvImportController(
            $this->mockFrameworkWithUploader(),
            $connection,
            new RequestStack(),
            $this->createStub(TranslatorInterface::class),
            $this->getFixturesDir(),
        );

        $this->expectException(InternalServerErrorException::class);

        $controller->importListWizardAction($this->mockDataContainer());
    }

    public function testFailsIfThereAreNoFiles(): void
    {
        $request = new Request();
        $request->query->set('key', 'lw');
        $request->request->set('FORM_SUBMIT', 'tl_csv_import_lw');

        $response = $this
            ->getController($request, null, $this->mockFramework([], true))
            ->importListWizardAction($this->mockDataContainer())
        ;

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testFailsIfTheFileExtensionIsNotCsv(): void
    {
        $request = new Request();
        $request->query->set('key', 'lw');
        $request->request->set('FORM_SUBMIT', 'tl_csv_import_lw');

        $response = $this
            ->getController($request, null, $this->mockFramework(['files/data.jpg'], true))
            ->importListWizardAction($this->mockDataContainer())
        ;

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    private function mockFramework(array $files = [], bool $expectError = false): ContaoFramework&Stub
    {
        $uploader = $this->createStub(FileUpload::class);
        $uploader
            ->method('uploadTo')
            ->willReturn($files)
        ;

        $adapter = $this->createAdapterMock(['addError']);
        $adapter
            ->expects($expectError ? $this->once() : $this->never())
            ->method('addError')
        ;

        $framework = $this->createContaoFrameworkStub([Message::class => $adapter]);
        $framework
            ->method('createInstance')
            ->willReturn($uploader)
        ;

        return $framework;
    }

    private function getController(Request|null $request = null, Connection|null $connection = null, ContaoFramework|null $framework = null): CsvImportController
    {
        $request ??= new Request();
        $request->setSession($this->mockSession());

        $requestStack = new RequestStack([$request]);

        $twig = $this->createStub(Environment::class);
        $twig
            ->method('render')
            ->willReturn('<content>')
        ;

        $authorizationChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->method('isGranted')
            ->willReturn(true)
        ;

        $container = $this->getContainerWithFixtures();
        $container->set('twig', $twig);
        $container->set('security.authorization_checker', $authorizationChecker);
        $container->set('request_stack', $requestStack);

        System::setContainer($container);

        $translator = $this->createStub(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $controller = new CsvImportController(
            $framework ?? $this->mockFrameworkWithUploader(),
            $connection ?? $this->createStub(Connection::class),
            $requestStack,
            $translator,
            $this->getFixturesDir(),
        );

        $controller->setContainer($container);

        return $controller;
    }

    private function mockDataContainer(): DataContainer&Stub
    {
        $mock = $this->createClassWithPropertiesStub(DataContainer::class);
        $mock->id = 1;
        $mock->table = 'tl_content';

        $mock
            ->method('getCurrentRecord')
            ->willReturn(['id' => 1, 'table' => 'tl_content'])
        ;

        return $mock;
    }

    private function mockFrameworkWithUploader(): ContaoFramework&Stub
    {
        $uploader = $this->createStub(FileUpload::class);
        $uploader
            ->method('uploadTo')
            ->willReturn(['files/data/data.csv'])
        ;

        $framework = $this->createContaoFrameworkStub();
        $framework
            ->method('createInstance')
            ->willReturn($uploader)
        ;

        return $framework;
    }
}
