<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller;

use Contao\CoreBundle\Controller\BackendCsvImportController;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\LanguageHelper;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\FileUpload;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the BackendControllerTest class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class BackendCsvImportControllerTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $GLOBALS['TL_LANG']['MSC'] = new LanguageHelper();
    }

    /**
     * {@inheritdoc}
     */
    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        unset($GLOBALS['TL_LANG']['MSC']);
    }

    /**
     * Tests the list wizard import.
     */
    public function testRendersTheListWizardMarkup()
    {
        $dc = $this->createMock(DataContainer::class);

        $dc
            ->method('__get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'id':
                        return 1;

                    case 'table':
                        return 'tl_content';

                    default:
                        return null;
                }
            })
        ;

        $expect = <<<'EOF'
<form id="tl_csv_import_lw">
  <div class="uploader"></div>
</form>

EOF;

        $request = new Request();
        $request->query->set('key', 'lw');

        $this->assertSame($expect, $this->getController($request)->importListWizard($dc)->getContent());
    }

    /**
     * Tests the list wizard import with POST data.
     */
    public function testImportsTheListWizardData()
    {
        $dc = $this->createMock(DataContainer::class);

        $dc
            ->method('__get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'id':
                        return 1;

                    case 'table':
                        return 'tl_content';

                    default:
                        return null;
                }
            })
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
            $this->mockContaoFrameworkWithUploader(),
            $connection,
            $requestStack,
            $this->getRootDir()
        );

        $response = $controller->importListWizard($dc);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    /**
     * Tests the table wizard import.
     */
    public function testRendersTheTableWizardMarkup()
    {
        $dc = $this->createMock(DataContainer::class);

        $dc
            ->method('__get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'id':
                        return 1;

                    case 'table':
                        return 'tl_content';

                    default:
                        return null;
                }
            })
        ;

        $expect = <<<'EOF'
<form id="tl_csv_import_tw">
  <div class="uploader"></div>
</form>

EOF;

        $request = new Request();
        $request->query->set('key', 'tw');

        $this->assertSame($expect, $this->getController($request)->importTableWizard($dc)->getContent());
    }

    /**
     * Tests the table wizard import with POST data.
     */
    public function testImportsTheTableWizardData()
    {
        $dc = $this->createMock(DataContainer::class);

        $dc
            ->method('__get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'id':
                        return 1;

                    case 'table':
                        return 'tl_content';

                    default:
                        return null;
                }
            })
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
            $this->mockContaoFrameworkWithUploader(),
            $connection,
            $requestStack,
            $this->getRootDir()
        );

        $response = $controller->importTableWizard($dc);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    /**
     * Tests the option wizard import.
     */
    public function testRendersTheOptionWizardMarkup()
    {
        $dc = $this->createMock(DataContainer::class);

        $dc
            ->method('__get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'id':
                        return 1;

                    case 'table':
                        return 'tl_content';

                    default:
                        return null;
                }
            })
        ;

        $expect = <<<'EOF'
<form id="tl_csv_import_ow">
  <div class="uploader"></div>
</form>

EOF;

        $request = new Request();
        $request->query->set('key', 'ow');

        $this->assertSame($expect, $this->getController($request)->importOptionWizard($dc)->getContent());
    }

    /**
     * Tests the option wizard import with POST data.
     */
    public function testImportsTheOptionWizardData()
    {
        $dc = $this->createMock(DataContainer::class);

        $dc
            ->method('__get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'id':
                        return 1;

                    case 'table':
                        return 'tl_content';

                    default:
                        return null;
                }
            })
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
            $this->mockContaoFrameworkWithUploader(),
            $connection,
            $requestStack,
            $this->getRootDir()
        );

        $response = $controller->importOptionWizard($dc);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    /**
     * Tests the list wizard import with incomplete POST data.
     */
    public function testRedirectsIfThePostDataIsIncomplete()
    {
        $dc = $this->createMock(DataContainer::class);

        $dc
            ->method('__get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'id':
                        return 1;

                    case 'table':
                        return 'tl_content';

                    default:
                        return null;
                }
            })
        ;

        $request = new Request();
        $request->query->set('key', 'lw');
        $request->request->set('FORM_SUBMIT', 'tl_csv_import_lw');

        $response = $this->getController($request)->importListWizard($dc);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(303, $response->getStatusCode());
    }

    /**
     * Tests the wizard import without a request object.
     */
    public function testFailsIfThereIsNoRequestObject()
    {
        $dc = $this->createMock(DataContainer::class);

        $dc
            ->method('__get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'id':
                        return 1;

                    case 'table':
                        return 'tl_content';

                    default:
                        return null;
                }
            })
        ;

        $controller = new BackendCsvImportController(
            $this->mockContaoFrameworkWithUploader(),
            $this->createMock(Connection::class),
            new RequestStack(),
            $this->getRootDir()
        );

        $this->expectException(InternalServerErrorException::class);

        $controller->importListWizard($dc);
    }

    /**
     * Returns the controller.
     *
     * @param Request|null $request
     *
     * @return BackendCsvImportController
     */
    private function getController(Request $request = null)
    {
        parent::setUp();

        if (null === $request) {
            $request = new Request();
        }

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new BackendCsvImportController(
            $this->mockContaoFrameworkWithUploader(),
            $this->createMock(Connection::class),
            $requestStack,
            $this->getRootDir()
        );
    }

    /**
     * Mocks a Contao framework with a file uploader.
     *
     * @return ContaoFramework|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockContaoFrameworkWithUploader()
    {
        $uploader = $this->createMock(FileUpload::class);

        $uploader
            ->method('uploadTo')
            ->willReturn(['files/data.csv'])
        ;

        return $this->mockContaoFramework(null, null, [], [FileUpload::class => $uploader]);
    }
}
