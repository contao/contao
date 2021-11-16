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

use Contao\Controller;
use Contao\CoreBundle\Exception\InvalidResourceException;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\FigureBuilder;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FilesModel;
use Contao\System;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\Request;

class ControllerTest extends TestCase
{
    use ExpectDeprecationTrait;

    public function testReturnsTheTimeZones(): void
    {
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

    public function testGeneratesTheMargin(): void
    {
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
        $container->set(Studio::class, $studio);
        $container->set(InsertTagParser::class, $insertTagParser);
        $container->setParameter('contao.resources_paths', $this->getTempDir());

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
            ->method('log')
            ->with(
                LogLevel::ERROR,
                'Image "/path/to/image.jpg" could not be processed: <error>',
                ['contao' => new ContaoContext('Contao\Controller::addImageToTemplate', 'ERROR')]
            )
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
        $container->set(Studio::class, $studio);
        $container->set(InsertTagParser::class, $insertTagParser);
        $container->set('monolog.logger.contao', $logger);

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
}
