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

use Contao\CoreBundle\File\MetaData;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\Studio;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
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
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @internal
     */
    public function __construct(Studio $studio, Environment $twig)
    {
        $this->studio = $studio;
        $this->twig = $twig;

        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * Render a figure. The provided configuration array is used to configure
     * a FigureBuilder. If not explicitly set the default figure template will
     * be used to render the results.
     */
    public function render($from, array $configuration = [], $template = '@ContaoCore/Image/Studio/figure.html.twig'): string
    {
        $configuration['from'] = $from;

        // Allow overwriting meta data on the fly
        foreach (['metaData', 'setMetaData'] as $key) {
            if (\is_array($configuration[$key] ?? null)) {
                $configuration[$key] = new MetaData($configuration[$key]);
            }
        }

        $figure = $this->buildFigure($configuration);

        return $this->twig->render($template, ['figure' => $figure]);
    }

    private function buildFigure(array $options): Figure
    {
        $figureBuilder = $this->studio->createFigureBuilder();

        foreach ($options as $property => $value) {
            $this->propertyAccessor->setValue($figureBuilder, $property, $value);
        }

        return $figureBuilder->build();
    }
}
