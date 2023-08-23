<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller;

use Contao\Config;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Controller\BackendCsvImportController;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\FileUpload;
use Contao\Message;
use Contao\System;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Contracts\Translation\TranslatorInterface;

class BackendCsvImportControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $finder = new ResourceFinder($this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao');

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('session', new Session(new MockArraySessionStorage()));
        $container->set('contao.resource_finder', $finder);
        $container->set('contao.insert_tag.parser', new InsertTagParser($this->mockContaoFramework(), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class)));

        System::setContainer($container);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME']);

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
            $this->mockFrameworkWithUploader(),
            $connection,
            $requestStack,
            $this->createMock(TranslatorInterface::class),
            $this->getFixturesDir(),
        );

        $response = $controller->importListWizardAction($this->mockDataContainer());

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
            $this->mockFrameworkWithUploader(),
            $connection,
            $requestStack,
            $this->createMock(TranslatorInterface::class),
            $this->getFixturesDir(),
        );

        $response = $controller->importTableWizardAction($this->mockDataContainer());

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
                ['id' => 1],
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
            $this->mockFrameworkWithUploader(),
            $connection,
            $requestStack,
            $this->createMock(TranslatorInterface::class),
            $this->getFixturesDir(),
        );

        $response = $controller->importOptionWizardAction($this->mockDataContainer());

        $this->assertInstanceOf(RedirectResponse::class, $response);
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
            $this->getFixturesDir(),
        );

        $response = $controller->importListWizardAction($this->mockDataContainer());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testFailsIfThereIsNoRequestObject(): void
    {
        $connection = $this->createMock(Connection::class);

        $controller = new BackendCsvImportController(
            $this->mockFrameworkWithUploader(),
            $connection,
            new RequestStack(),
            $this->createMock(TranslatorInterface::class),
            $this->getFixturesDir(),
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
            $this->getFixturesDir(),
        );

        $response = $controller->importListWizardAction($this->mockDataContainer());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
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
            $this->getFixturesDir(),
        );

        $response = $controller->importListWizardAction($this->mockDataContainer());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    /**
     * @return ContaoFramework&MockObject
     */
    private function mockFramework(array $files = [], bool $expectError = false): ContaoFramework
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

    private function getController(Request|null $request = null): BackendCsvImportController
    {
        $requestStack = new RequestStack();
        $requestStack->push($request ?: new Request());

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        return new BackendCsvImportController(
            $this->mockFrameworkWithUploader(),
            $this->createMock(Connection::class),
            $requestStack,
            $translator,
            $this->getFixturesDir(),
        );
    }

    /**
     * @return DataContainer&MockObject
     */
    private function mockDataContainer(): DataContainer
    {
        $mock = $this->mockClassWithProperties(DataContainer::class);
        $mock->id = 1;
        $mock->table = 'tl_content';

        return $mock;
    }

    /**
     * @return ContaoFramework&MockObject
     */
    private function mockFrameworkWithUploader(): ContaoFramework
    {
        $uploader = $this->createMock(FileUpload::class);
        $uploader
            ->method('uploadTo')
            ->willReturn(['files/data/data.csv'])
        ;

        $framework = $this->mockContaoFramework();
        $framework
            ->method('createInstance')
            ->willReturn($uploader)
        ;

        return $framework;
    }
}
