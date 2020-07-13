<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Runtime;

use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\Studio;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

final class FigureRendererRuntime implements RuntimeExtensionInterface
{
    /**
     * @var Studio
     */
    private $studio;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @internal
     */
    public function __construct(Studio $studio, Environment $twig)
    {
        $this->studio = $studio;
        $this->twig = $twig;
    }

    /**
     * Render a figure. The provided configuration array is used to configure
     * a FigureBuilder. If not explicitly set the default figure template will
     * be used to render the results.
     */
    public function render($from, array $configuration = [], $template = '@ContaoCore/Image/Studio/figure.html.twig'): string
    {
        $configuration['from'] = $from;

        $figure = $this->buildFigure($configuration);

        return $this->twig->render($template, ['figure' => $figure]);
    }

    private function buildFigure(array $options): Figure
    {
        $figureBuilder = $this->studio->createFigureBuilder();
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        foreach ($options as $property => $value) {
            $propertyAccessor->setValue($figureBuilder, $property, $value);
        }

        return $figureBuilder->build();
    }
}
