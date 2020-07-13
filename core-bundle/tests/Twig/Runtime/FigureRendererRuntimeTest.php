<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Runtime;

use Contao\CoreBundle\File\MetaData;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\FigureBuilder;
use Contao\CoreBundle\Image\Studio\ImageResult;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Runtime\FigureRendererRuntime;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Twig\Environment;

class FigureRendererRuntimeTest extends TestCase
{
    public function testConfiguresBuilder(): void
    {
        $metaData = new MetaData([]);

        $configuration = [
            'size' => '_size',
            'metadata' => $metaData,
            'disableMetaData' => true,
            'locale' => 'de',
            'linkAttributes' => ['foo' => 'bar'],
            'linkHref' => 'foo',
            'lightBoxResourceOrUrl' => 'foobar',
            'lightBoxSize' => '_lightbox_size',
            'lightBoxGroupIdentifier' => '123',
            'enableLightBox' => true,
            'options' => ['foo' => 'bar'],
        ];

        $expectedFigureBuilderCalls = [
            'from' => 'resource',
            'setSize' => '_size',
            'setMetaData' => $metaData,
            'disableMetaData' => true,
            'setLocale' => 'de',
            'setLinkAttributes' => ['foo' => 'bar'],
            'setLinkHref' => 'foo',
            'setLightBoxResourceOrUrl' => 'foobar',
            'setLightBoxSize' => '_lightbox_size',
            'setLightBoxGroupIdentifier' => '123',
            'enableLightBox' => true,
            'setOptions' => ['foo' => 'bar'],
        ];

        $runtime = $this->getRuntime($expectedFigureBuilderCalls);

        $this->assertSame('<result>', $runtime->render('resource', $configuration));
    }

    public function testUsesCustomTemplate(): void
    {
        $runtime = $this->getRuntime([], '@App/custom_figure.html.twig');

        $this->assertSame('<result>', $runtime->render(1, [], '@App/custom_figure.html.twig'));
    }

    public function testFailsWithInvalidConfiguration(): void
    {
        $this->expectException(NoSuchPropertyException::class);

        $configuration = [
            'invalid' => 'foobar',
        ];

        $this->getRuntime()->render(1, $configuration);
    }

    private function getRuntime(array $figureBuilderCalls = [], string $expectedTemplate = '@ContaoCore/Image/Studio/figure.html.twig'): FigureRendererRuntime
    {
        $figure = new Figure($this->createMock(ImageResult::class));

        $figureBuilder = $this->createMock(FigureBuilder::class);

        $figureBuilder
            ->method('build')
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

        return new FigureRendererRuntime($studio, $twig);
    }
}
