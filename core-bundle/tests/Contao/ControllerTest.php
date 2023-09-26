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
use Contao\Controller;
use Contao\CoreBundle\Exception\InvalidResourceException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\FigureBuilder;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DcaExtractor;
use Contao\DcaLoader;
use Contao\Environment;
use Contao\FilesModel;
use Contao\PageModel;
use Contao\System;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class ControllerTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function setUp(): void
    {
        parent::setUp();

        Controller::resetControllerCache();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANG'], $GLOBALS['TL_MIME']);

        $this->resetStaticProperties([
            DcaExtractor::class,
            DcaLoader::class,
            System::class,
            Config::class,
            Environment::class,
            Controller::class,
        ]);

        parent::tearDown();
    }

    /**
     * @group legacy
     */
    public function testReturnsTheTimeZones(): void
    {
        $this->expectDeprecation('%sgetTimeZones%shas been deprecated%s');
        $this->expectDeprecation('%stimezones.php%s');

        $timeZones = System::getTimeZones();

        $this->assertCount(9, $timeZones['General']);
        $this->assertCount(51, $timeZones['Africa']);
        $this->assertCount(140, $timeZones['America']);
        $this->assertCount(10, $timeZones['Antarctica']);
        $this->assertCount(83, $timeZones['Asia']);
        $this->assertCount(11, $timeZones['Atlantic']);
        $this->assertCount(22, $timeZones['Australia']);
        $this->assertCount(4, $timeZones['Brazil']);
        $this->assertCount(9, $timeZones['Canada']);
        $this->assertCount(2, $timeZones['Chile']);
        $this->assertCount(53, $timeZones['Europe']);
        $this->assertCount(11, $timeZones['Indian']);
        $this->assertCount(4, $timeZones['Brazil']);
        $this->assertCount(3, $timeZones['Mexico']);
        $this->assertCount(40, $timeZones['Pacific']);
        $this->assertCount(13, $timeZones['United States']);
    }

    /**
     * @group legacy
     */
    public function testGeneratesTheMargin(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.13: Using Contao\Controller::generateMargin is deprecated%s');

        $margins = [
            'top' => '40px',
            'right' => '10%',
            'bottom' => '-2px',
            'left' => '-50%',
            'unit' => '',
        ];

        $this->assertSame('margin:40px 10% -2px -50%;', Controller::generateMargin($margins));
    }

    /**
     * @group legacy
     *
     * @dataProvider provideAddImageToTemplateScenarios
     */
    public function testAddImageToTemplateDelegatesToFigureBuilder(array $inputs, array $expected): void
    {
        $rowData = $inputs['rowData'] ?? null;
        $filesModel = $inputs['filesModel'] ?? null;
        $maxWidth = $inputs['maxWidth'] ?? null;

        $expectedPath = $expected['path'] ?? null;
        $expectedSize = $expected['size'] ?? null;
        $expectedEnableLightbox = $expected['lightbox'] ?? null;
        $expectedLightboxSize = $expected['lightboxSize'] ?? null;
        $expectedMetadata = $expected['metadata'] ?? null;
        $expectedIncludeFullMetadata = $expected['fullMetadata'] ?? false;
        $expectedLinkTitle = $expected['linkTitle'] ?? '';
        $expectedMargin = $expected['margin'] ?? null;
        $expectedFloating = $expected['floating'] ?? null;

        $template = new \stdClass();

        $figure = $this->createMock(Figure::class);
        $figure
            ->expects($this->once())
            ->method('applyLegacyTemplateData')
            ->with($template, $expectedMargin, $expectedFloating, $expectedIncludeFullMetadata)
        ;

        $figureBuilder = $this->createMock(FigureBuilder::class);
        $figureBuilder
            ->expects(null !== $filesModel ? $this->once() : $this->never())
            ->method('fromFilesModel')
            ->willReturnCallback(
                function (FilesModel $model) use ($figureBuilder, $rowData) {
                    $this->assertSame($rowData['singleSRC'], $model->path, 'files model path matches row data');

                    return $figureBuilder;
                }
            )
        ;

        $figureBuilder
            ->expects(null !== $expectedPath ? $this->once() : $this->never())
            ->method('fromPath')
            ->with($expectedPath, false)
            ->willReturn($figureBuilder)
        ;

        $figureBuilder
            ->expects($this->once())
            ->method('setMetadata')
            ->with($expectedMetadata)
            ->willReturn($figureBuilder)
        ;

        $figureBuilder
            ->expects($this->once())
            ->method('setSize')
            ->with($expectedSize)
            ->willReturn($figureBuilder)
        ;

        $figureBuilder
            ->expects($this->once())
            ->method('setLightboxGroupIdentifier')
            ->with('lightbox-123')
            ->willReturn($figureBuilder)
        ;

        $figureBuilder
            ->expects($this->once())
            ->method('setLightboxSize')
            ->with($expectedLightboxSize)
            ->willReturn($figureBuilder)
        ;

        $figureBuilder
            ->expects($this->once())
            ->method('enableLightbox')
            ->with($expectedEnableLightbox)
            ->willReturn($figureBuilder)
        ;

        $figureBuilder
            ->expects($this->once())
            ->method('buildIfResourceExists')
            ->willReturn($figure)
        ;

        $studio = $this->createMock(Studio::class);
        $studio
            ->method('createFigureBuilder')
            ->willReturn($figureBuilder)
        ;

        // Prepare environment
        $insertTagParser = $this->createMock(InsertTagParser::class);
        $insertTagParser
            ->method('replaceInline')
            ->willReturnArgument(0)
        ;

        $insertTagParser
            ->method('replace')
            ->willReturnArgument(0)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.image.studio', $studio);
        $container->set('contao.insert_tag.parser', $insertTagParser);
        $container->setParameter('contao.resources_paths', $this->getTempDir());

        (new Filesystem())->mkdir($this->getTempDir().'/languages/en');

        System::setContainer($container);

        $GLOBALS['TL_DCA']['tl_files']['fields']['meta']['eval']['metaFields'] = ['caption' => null];

        $this->expectDeprecation('%sUsing %saddImageToTemplate() is deprecated%s');

        Controller::addImageToTemplate($template, $rowData, $maxWidth, 'lightbox-123', $filesModel);

        // Reset environment
        unset($GLOBALS['TL_DCA']);

        $this->assertSame(['linkTitle' => $expectedLinkTitle], get_object_vars($template), 'link title gets set');
    }

    public function provideAddImageToTemplateScenarios(): \Generator
    {
        yield 'resource from path, fallback metadata from row, link title' => [
            [
                'rowData' => [
                    'singleSRC' => '/path/to/file.jpg',
                    'linkTitle' => 'link title',
                    'title' => 'spread some <3',
                ],
            ],
            [
                'path' => '/path/to/file.jpg',
                'metadata' => new Metadata([
                    Metadata::VALUE_ALT => '',
                    Metadata::VALUE_TITLE => '',
                    Metadata::VALUE_URL => '',
                    'linkTitle' => 'link title',
                ]),
                'linkTitle' => 'spread some &lt;3',
            ],
        ];

        $filesModel = (new \ReflectionClass(FilesModel::class))->newInstanceWithoutConstructor();
        $filesModel->path = '/path/that/should/get/corrected';

        yield 'resource from files model' => [
            [
                'rowData' => [
                    'singleSRC' => '/path/to/file.jpg',
                ],
                'filesModel' => $filesModel,
            ],
            [
                'fullMetadata' => true,
            ],
        ];

        yield 'resource from files model with metadata overwrites' => [
            [
                'rowData' => [
                    'singleSRC' => '/path/to/file.jpg',
                    'overwriteMeta' => '1',
                    'caption' => 'foo caption',
                ],
                'filesModel' => $filesModel,
            ],
            [
                'metadata' => new Metadata([
                    Metadata::VALUE_CAPTION => 'foo caption',
                ]),
                'fullMetadata' => true,
            ],
        ];

        yield 'set size, lightbox size, fullsize, margin, floating' => [
            [
                'rowData' => [
                    'singleSRC' => '/path/to/file.jpg',
                    'size' => '_my_size',
                    'lightboxSize' => '_lightbox_size',
                    'fullsize' => '1',
                    'imagemargin' => serialize(['left' => 1, 'right' => 2, 'top' => 3, 'bottom' => 4, 'unit' => 'em']),
                    'floating' => 'left',
                ],
                'filesModel' => $filesModel,
            ],
            [
                'fullMetadata' => true,
                'size' => '_my_size',
                'lightboxSize' => '_lightbox_size',
                'lightbox' => true,
                'margin' => ['left' => 1, 'right' => 2, 'top' => 3, 'bottom' => 4, 'unit' => 'em'],
                'floating' => 'left',
            ],
        ];

        yield 'crop size with max width and margin' => [
            [
                'rowData' => [
                    'singleSRC' => '/path/to/file.jpg',
                    'size' => serialize([200, 100, 'crop']),
                    'imagemargin' => serialize(['left' => 10, 'right' => 20, 'top' => 30, 'bottom' => 40, 'unit' => 'px']),
                ],
                'maxWidth' => 75,
                'filesModel' => $filesModel,
            ],
            [
                'fullMetadata' => true,
                'size' => [45, 22, 'crop'],
                'margin' => ['left' => 10, 'right' => 20, 'top' => 30, 'bottom' => 40, 'unit' => 'px'],
            ],
        ];
    }

    /**
     * @group legacy
     */
    public function testAddImageToTemplateLogsAndCreatesFallbackDataWhenNoFigureIsBuilt(): void
    {
        $figureBuilder = $this->createMock(FigureBuilder::class);

        foreach (['fromPath', 'setMetadata', 'setSize', 'setLightboxGroupIdentifier', 'setLightboxSize', 'enableLightbox'] as $method) {
            $figureBuilder
                ->method($method)
                ->willReturn($figureBuilder)
            ;
        }

        $figureBuilder
            ->method('buildIfResourceExists')
            ->willReturn(null)
        ;

        $figureBuilder
            ->method('getLastException')
            ->willReturn(new InvalidResourceException('<error>'))
        ;

        $studio = $this->createMock(Studio::class);
        $studio
            ->method('createFigureBuilder')
            ->willReturn($figureBuilder)
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Image "/path/to/image.jpg" could not be processed: <error>')
        ;

        $template = new \stdClass();

        // Prepare environment
        $insertTagParser = $this->createMock(InsertTagParser::class);
        $insertTagParser
            ->method('replaceInline')
            ->willReturnArgument(0)
        ;

        $insertTagParser
            ->method('replace')
            ->willReturnArgument(0)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.image.studio', $studio);
        $container->set('contao.insert_tag.parser', $insertTagParser);
        $container->set('monolog.logger.contao.error', $logger);

        System::setContainer($container);

        $this->expectDeprecation('%sUsing %saddImageToTemplate() is deprecated%s');

        Controller::addImageToTemplate($template, ['singleSRC' => '/path/to/image.jpg']);

        $expectedTemplateData = [
            'width' => null,
            'height' => null,
            'picture' => [
                'img' => [
                    'src' => '',
                    'srcset' => '',
                ],
                'sources' => [],
                'alt' => '',
                'title' => '',
            ],
            'singleSRC' => '/path/to/image.jpg',
            'src' => '',
            'linkTitle' => '',
            'margin' => '',
            'addImage' => true,
            'addBefore' => true,
            'fullsize' => false,
        ];

        $this->assertSame($expectedTemplateData, get_object_vars($template));
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddToUrlWithoutQueryString(): void
    {
        \define('TL_SCRIPT', '');

        $request = new Request();
        $request->attributes->set('_contao_referer_id', 'cri');

        $container = $this->getContainerWithContaoConfiguration();
        $container->get('request_stack')->push($request);

        System::setContainer($container);

        $this->assertSame('', Controller::addToUrl(''));
        $this->assertSame('?do=page&amp;ref=cri', Controller::addToUrl('do=page'));
        $this->assertSame('?do=page&amp;rt=foo&amp;ref=cri', Controller::addToUrl('do=page&amp;rt=foo'));
        $this->assertSame('?do=page&amp;ref=cri', Controller::addToUrl('do=page&amp;ref=bar'));
        $this->assertSame('?act=edit&amp;id=2&amp;ref=cri', Controller::addToUrl('act=edit&id=2'));
        $this->assertSame('?act=edit&amp;id=2&amp;ref=cri', Controller::addToUrl('act=edit&amp;id=2'));
        $this->assertSame('?act=edit&amp;foo=%2B&amp;bar=%20&amp;ref=cri', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20'));

        $this->assertSame('', Controller::addToUrl('', false));
        $this->assertSame('?do=page', Controller::addToUrl('do=page', false));
        $this->assertSame('?do=page&amp;rt=foo', Controller::addToUrl('do=page&amp;rt=foo', false));
        $this->assertSame('?do=page&amp;ref=bar', Controller::addToUrl('do=page&amp;ref=bar', false));
        $this->assertSame('?act=edit&amp;id=2', Controller::addToUrl('act=edit&id=2', false));
        $this->assertSame('?act=edit&amp;id=2', Controller::addToUrl('act=edit&amp;id=2', false));
        $this->assertSame('?act=edit&amp;foo=%2B&amp;bar=%20', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20', false));

        $request->query->set('ref', 'ref');

        $this->assertSame('?ref=cri', Controller::addToUrl('', false));
        $this->assertSame('?do=page&amp;ref=cri', Controller::addToUrl('do=page', false));
        $this->assertSame('?do=page&amp;rt=foo&amp;ref=cri', Controller::addToUrl('do=page&amp;rt=foo', false));
        $this->assertSame('?do=page&amp;ref=cri', Controller::addToUrl('do=page&amp;ref=bar', false));
        $this->assertSame('?act=edit&amp;id=2&amp;ref=cri', Controller::addToUrl('act=edit&id=2', false));
        $this->assertSame('?act=edit&amp;id=2&amp;ref=cri', Controller::addToUrl('act=edit&amp;id=2', false));
        $this->assertSame('?act=edit&amp;foo=%2B&amp;bar=%20&amp;ref=cri', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20', false));
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddToUrlWithQueryString(): void
    {
        \define('TL_SCRIPT', '');

        $request = new Request();
        $request->attributes->set('_contao_referer_id', 'cri');
        $request->server->set('QUERY_STRING', 'do=page&id=4');

        $container = $this->getContainerWithContaoConfiguration();
        $container->get('request_stack')->push($request);

        System::setContainer($container);

        $this->assertSame('?do=page&amp;id=4', Controller::addToUrl(''));
        $this->assertSame('?do=page&amp;id=4&amp;ref=cri', Controller::addToUrl('do=page'));
        $this->assertSame('?do=page&amp;id=4&amp;rt=foo&amp;ref=cri', Controller::addToUrl('do=page&amp;rt=foo'));
        $this->assertSame('?do=page&amp;id=4&amp;ref=cri', Controller::addToUrl('do=page&amp;ref=bar'));
        $this->assertSame('?do=page&amp;id=2&amp;act=edit&amp;ref=cri', Controller::addToUrl('act=edit&id=2'));
        $this->assertSame('?do=page&amp;id=2&amp;act=edit&amp;ref=cri', Controller::addToUrl('act=edit&amp;id=2'));
        $this->assertSame('?do=page&amp;id=4&amp;act=edit&amp;foo=%2B&amp;bar=%20&amp;ref=cri', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20'));
        $this->assertSame('?do=page&amp;key=foo&amp;ref=cri', Controller::addToUrl('key=foo', true, ['id']));

        $this->assertSame('?do=page&amp;id=4', Controller::addToUrl('', false));
        $this->assertSame('?do=page&amp;id=4', Controller::addToUrl('do=page', false));
        $this->assertSame('?do=page&amp;id=4&amp;rt=foo', Controller::addToUrl('do=page&amp;rt=foo', false));
        $this->assertSame('?do=page&amp;id=4&amp;ref=bar', Controller::addToUrl('do=page&amp;ref=bar', false));
        $this->assertSame('?do=page&amp;id=2&amp;act=edit', Controller::addToUrl('act=edit&id=2', false));
        $this->assertSame('?do=page&amp;id=2&amp;act=edit', Controller::addToUrl('act=edit&amp;id=2', false));
        $this->assertSame('?do=page&amp;id=4&amp;act=edit&amp;foo=%2B&amp;bar=%20', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20', false));
        $this->assertSame('?do=page&amp;key=foo', Controller::addToUrl('key=foo', false, ['id']));

        $request->query->set('ref', 'ref');

        $this->assertSame('?do=page&amp;id=4&amp;ref=cri', Controller::addToUrl('', false));
        $this->assertSame('?do=page&amp;id=4&amp;ref=cri', Controller::addToUrl('do=page', false));
        $this->assertSame('?do=page&amp;id=4&amp;rt=foo&amp;ref=cri', Controller::addToUrl('do=page&amp;rt=foo', false));
        $this->assertSame('?do=page&amp;id=4&amp;ref=cri', Controller::addToUrl('do=page&amp;ref=bar', false));
        $this->assertSame('?do=page&amp;id=2&amp;act=edit&amp;ref=cri', Controller::addToUrl('act=edit&id=2', false));
        $this->assertSame('?do=page&amp;id=2&amp;act=edit&amp;ref=cri', Controller::addToUrl('act=edit&amp;id=2', false));
        $this->assertSame('?do=page&amp;id=4&amp;act=edit&amp;foo=%2B&amp;bar=%20&amp;ref=cri', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20', false));
        $this->assertSame('?do=page&amp;key=foo&amp;ref=cri', Controller::addToUrl('key=foo', true, ['id']));
    }

    /**
     * @dataProvider pageStatusIconProvider
     */
    public function testPageStatusIcon(PageModel $pageModel, string $expected): void
    {
        $this->assertSame($expected, Controller::getPageStatusIcon($pageModel));
        $this->assertFileExists(__DIR__.'/../../contao/themes/flexible/icons/'.$expected);
    }

    public function pageStatusIconProvider(): \Generator
    {
        yield 'Published' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => '',
                'protected' => '',
                'start' => '',
                'stop' => '',
                'published' => '1',
            ]),
            'regular.svg',
        ];

        yield 'Unpublished' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => '',
                'protected' => '',
                'start' => '',
                'stop' => '',
                'published' => '',
            ]),
            'regular_1.svg',
        ];

        yield 'Hidden in menu' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => '1',
                'protected' => '',
                'start' => '',
                'stop' => '',
                'published' => '1',
            ]),
            'regular_2.svg',
        ];

        yield 'Unpublished and hidden from menu' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => '1',
                'protected' => '',
                'start' => '',
                'stop' => '',
                'published' => '',
            ]),
            'regular_3.svg',
        ];

        yield 'Protected' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => '',
                'protected' => '1',
                'start' => '',
                'stop' => '',
                'published' => '1',
            ]),
            'regular_4.svg',
        ];

        yield 'Unpublished and protected' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => '',
                'protected' => '1',
                'start' => '',
                'stop' => '',
                'published' => '',
            ]),
            'regular_5.svg',
        ];

        yield 'Unpublished and protected and hidden from menu' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => '1',
                'protected' => '1',
                'start' => '',
                'stop' => '',
                'published' => '',
            ]),
            'regular_7.svg',
        ];

        yield 'Unpublished by stop date' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => '',
                'protected' => '',
                'start' => '',
                'stop' => '100',
                'published' => '1',
            ]),
            'regular_1.svg',
        ];

        yield 'Unpublished by start date' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => '',
                'protected' => '',
                'start' => PHP_INT_MAX,
                'stop' => '',
                'published' => '1',
            ]),
            'regular_1.svg',
        ];

        yield 'Root page' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'root',
                'hide' => '',
                'protected' => '',
                'start' => '',
                'stop' => '',
                'published' => '1',
            ]),
            'root.svg',
        ];

        yield 'Unpublished root page' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'root',
                'hide' => '',
                'protected' => '',
                'start' => '',
                'stop' => '',
                'published' => '',
            ]),
            'root_1.svg',
        ];

        yield 'Hidden root page' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'root',
                'hide' => '1',
                'protected' => '',
                'start' => '',
                'stop' => '',
                'published' => '1',
            ]),
            'root.svg',
        ];

        yield 'Protected root page' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'root',
                'hide' => '',
                'protected' => '1',
                'start' => '',
                'stop' => '',
                'published' => '1',
            ]),
            'root.svg',
        ];

        yield 'Root in maintenance mode' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'root',
                'hide' => '',
                'protected' => '',
                'maintenanceMode' => '1',
                'start' => '',
                'stop' => '',
                'published' => '1',
            ]),
            'root_2.svg',
        ];

        yield 'Unpublished root in maintenance mode' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'root',
                'hide' => '',
                'protected' => '',
                'maintenanceMode' => '1',
                'start' => '',
                'stop' => '',
                'published' => '',
            ]),
            'root_1.svg',
        ];
    }

    /**
     * @dataProvider redirectProvider
     *
     * @group legacy
     */
    public function testReplacesOldBePathsInRedirect(string $location, array $routes, string $expected): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->exactly(\count($routes)))
            ->method('generate')
            ->withConsecutive(...array_map(static fn ($route) => [$route], $routes))
            ->willReturnOnConsecutiveCalls(...array_map(static fn ($route) => '/'.$route, $routes))
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('router', $router);
        System::setContainer($container);

        Environment::reset();
        Environment::set('path', '');
        Environment::set('base', '');

        try {
            Controller::redirect($location);
        } catch (RedirectResponseException $exception) {
            /** @var RedirectResponse $response */
            $response = $exception->getResponse();

            $this->assertInstanceOf(RedirectResponse::class, $response);
            $this->assertSame($expected, $response->getTargetUrl());
        }
    }

    public function redirectProvider(): \Generator
    {
        yield 'Never calls the router without old backend path' => [
            'https://example.com',
            [],
            'https://example.com',
        ];

        yield 'Replaces multiple paths (not really expected)' => [
            'https://example.com/contao/main.php?contao/file.php=foo',
            ['contao_backend', 'contao_backend_file'],
            'https://example.com/contao_backend?contao_backend_file=foo',
        ];

        $pathMap = [
            'contao/confirm.php' => 'contao_backend_confirm',
            'contao/file.php' => 'contao_backend_file',
            'contao/help.php' => 'contao_backend_help',
            'contao/index.php' => 'contao_backend_login',
            'contao/main.php' => 'contao_backend',
            'contao/page.php' => 'contao_backend_page',
            'contao/password.php' => 'contao_backend_password',
            'contao/popup.php' => 'contao_backend_popup',
            'contao/preview.php' => 'contao_backend_preview',
        ];

        foreach ($pathMap as $old => $new) {
            yield 'Replaces '.$old.' with '.$new.' route' => [
                "https://example.com/$old?foo=bar",
                [$new],
                "https://example.com/$new?foo=bar",
            ];
        }
    }

    /**
     * @group legacy
     */
    public function testCachesOldBackendPaths(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->exactly(2))
            ->method('generate')
            ->withConsecutive(['contao_backend'], ['contao_backend_file'])
            ->willReturn('/contao', '/contao/file')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('router', $router);
        System::setContainer($container);

        Environment::reset();
        Environment::set('path', '');
        Environment::set('base', '');

        $ref = new \ReflectionClass(Controller::class);
        $method = $ref->getMethod('replaceOldBePaths');
        $method->setAccessible(true);

        $this->assertSame(
            $method->invoke(null, 'This is a template with link to <a href="/contao/main.php">backend main</a> and <a href="/contao/main.php?do=articles">articles</a>'),
            'This is a template with link to <a href="/contao">backend main</a> and <a href="/contao?do=articles">articles</a>'
        );

        $this->assertSame(
            $method->invoke(null, 'Link to <a href="/contao/main.php">backend main</a> and <a href="/contao/file.php?x=y">files</a>'),
            'Link to <a href="/contao">backend main</a> and <a href="/contao/file?x=y">files</a>'
        );
    }
}
