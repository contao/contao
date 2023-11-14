<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Image\Studio;

use Contao\Config;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\FigureBuilder;
use Contao\CoreBundle\Image\Studio\FigureRenderer;
use Contao\CoreBundle\Image\Studio\ImageResult;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\File;
use Contao\Files;
use Contao\Image\ImageInterface;
use Contao\System;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Twig\Environment;

class FigureRendererTest extends TestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME']);

        $this->resetStaticProperties([System::class, File::class, Files::class, Config::class]);

        parent::tearDown();
    }

    public function testConfiguresBuilder(): void
    {
        $metadata = new Metadata([]);

        $configuration = [
            'metadata' => $metadata,
            'disableMetadata' => true,
            'locale' => 'de',
            'linkAttributes' => ['foo' => 'bar'],
            'linkHref' => 'foo',
            'lightboxResourceOrUrl' => 'foobar',
            'lightboxSize' => '_lightbox_size',
            'lightboxGroupIdentifier' => '123',
            'enableLightbox' => true,
            'options' => ['foo' => 'bar'],
        ];

        $expectedFigureBuilderCalls = [
            'from' => 'resource',
            'setSize' => '_size',
            'setMetadata' => $metadata,
            'disableMetadata' => true,
            'setLocale' => 'de',
            'setLinkAttributes' => ['foo' => 'bar'],
            'setLinkHref' => 'foo',
            'setLightboxResourceOrUrl' => 'foobar',
            'setLightboxSize' => '_lightbox_size',
            'setLightboxGroupIdentifier' => '123',
            'enableLightbox' => true,
            'setOptions' => ['foo' => 'bar'],
        ];

        $figureRenderer = $this->getFigureRenderer($expectedFigureBuilderCalls);

        $this->assertSame('<result>', $figureRenderer->render('resource', '_size', $configuration));
    }

    /**
     * @dataProvider provideMetadataKeys
     */
    public function testAllowsDefiningMetadataAsArray(string $key): void
    {
        $metadata = [Metadata::VALUE_ALT => 'foo'];
        $figureRenderer = $this->getFigureRenderer(['setMetadata' => new Metadata($metadata)]);

        $this->assertSame('<result>', $figureRenderer->render('resource', null, [$key => [Metadata::VALUE_ALT => 'foo']]));
    }

    public function provideMetadataKeys(): \Generator
    {
        yield ['metadata'];
        yield ['setMetadata'];
    }

    public function testUsesCustomTemplate(): void
    {
        $figureRenderer = $this->getFigureRenderer([], '@App/custom_figure.html.twig');

        $this->assertSame('<result>', $figureRenderer->render(1, null, [], '@App/custom_figure.html.twig'));
    }

    public function testRendersContaoTemplate(): void
    {
        $image = $this->createMock(ImageResult::class);
        $image
            ->method('getImageSrc')
            ->willReturn('files/public/foo.jpg')
        ;

        $figureBuilder = $this->createMock(FigureBuilder::class);
        $figureBuilder
            ->method('buildIfResourceExists')
            ->willReturn(new Figure($image))
        ;

        $studio = $this->createMock(Studio::class);
        $studio
            ->method('createFigureBuilder')
            ->willReturn($figureBuilder)
        ;

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->never())
            ->method('render')
        ;

        // Make a template and image available at the temp dir
        $filesystem = new Filesystem();
        $filesystem->dumpFile(Path::join($this->getTempDir(), 'templates/foo.html5'), '<foo result>');

        $filesystem->symlink(
            Path::canonicalize(__DIR__.'/../../Fixtures/files'),
            Path::join($this->getTempDir(), 'files'),
        );

        $imageFactory = $this->createMock(ImageFactoryInterface::class);
        $imageFactory
            ->method('create')
            ->willReturn($this->createMock(ImageInterface::class))
        ;

        // Configure the container
        $container = $this->getContainerWithContaoConfiguration($this->getTempDir());
        $container->set('contao.security.token_checker', $this->createMock(TokenChecker::class));
        $container->set('filesystem', $filesystem);
        $container->set('contao.insert_tag.parser', new InsertTagParser($this->mockContaoFramework(), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class)));
        $container->set('contao.image.factory', $imageFactory);

        System::setContainer($container);

        // Render a figure with a PHP template
        $figureRenderer = new FigureRenderer($studio, $twig);

        $this->assertSame('<foo result>', $figureRenderer->render('files/public/foo.jpg', null, [], 'foo'));
    }

    public function testFailsWithInvalidConfiguration(): void
    {
        $figureRenderer = $this->getFigureRenderer();

        $this->expectException(NoSuchPropertyException::class);

        $figureRenderer->render(1, null, ['invalid' => 'foobar']);
    }

    /**
     * @dataProvider provideInvalidTemplates
     */
    public function testFailsWithInvalidTemplate(string $invalidTemplate): void
    {
        $figureRenderer = $this->getFigureRenderer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid Contao template name ".*"\./');

        $figureRenderer->render(1, null, [], $invalidTemplate);
    }

    public function provideInvalidTemplates(): \Generator
    {
        yield 'not treated as Twig template, has extension' => [
            'foo.twig',
        ];

        yield 'contains slashes' => [
            '/some/path/foo',
        ];

        yield 'contains whitespaces' => [
            'f oo',
        ];
    }

    public function testReturnsNullIfTheResourceDoesNotExist(): void
    {
        $figureBuilder = $this->createMock(FigureBuilder::class);
        $figureBuilder
            ->method('buildIfResourceExists')
            ->willReturn(null)
        ;

        $studio = $this->createMock(Studio::class);
        $studio
            ->method('createFigureBuilder')
            ->willReturn($figureBuilder)
        ;

        $twig = $this->createMock(Environment::class);
        $figureRenderer = new FigureRenderer($studio, $twig);

        $this->assertNull($figureRenderer->render('invalid-resource', null));
    }

    private function getFigureRenderer(array $figureBuilderCalls = [], string $expectedTemplate = '@ContaoCore/Image/Studio/figure.html.twig'): FigureRenderer
    {
        $figure = new Figure($this->createMock(ImageResult::class));

        $figureBuilder = $this->createMock(FigureBuilder::class);
        $figureBuilder
            ->method('buildIfResourceExists')
            ->willReturn($figure)
        ;

        foreach ($figureBuilderCalls as $method => $value) {
            $figureBuilder
                ->expects($this->once())
                ->method($method)
                ->with($value)
                ->willReturn($figureBuilder)
            ;
        }

        $studio = $this->createMock(Studio::class);
        $studio
            ->method('createFigureBuilder')
            ->willReturn($figureBuilder)
        ;

        $twig = $this->createMock(Environment::class);
        $twig
            ->method('render')
            ->with($expectedTemplate, ['figure' => $figure])
            ->willReturn('<result>')
        ;

        return new FigureRenderer($studio, $twig);
    }
}
