<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Image\Studio;

use Contao\CoreBundle\File\Metadata;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Contao\Image\ImageInterface;
use Contao\Image\PictureConfiguration;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Twig\Environment;

class FigureRenderer
{
    private Studio $studio;
    private Environment $twig;
    private PropertyAccessor $propertyAccessor;

    public function __construct(Studio $studio, Environment $twig)
    {
        $this->studio = $studio;
        $this->twig = $twig;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * Renders a figure.
     *
     * The provided configuration array is used to configure a FigureBuilder
     * object. If not explicitly set, the default figure template will be used
     * to render the results.
     *
     * Returns null if the resource is invalid.
     *
     * @param int|string|FilesModel|ImageInterface       $from          Can be a FilesModel, an ImageInterface, a tl_files UUID/ID/path or a file system path
     * @param int|string|array|PictureConfiguration|null $size          A picture size configuration or reference
     * @param array<string, mixed>                       $configuration Configuration for the FigureBuilder
     * @param string                                     $template      A Contao or Twig template
     */
    public function render($from, $size, array $configuration = [], string $template = '@ContaoCore/Image/Studio/figure.html.twig'): ?string
    {
        $configuration['from'] = $from;
        $configuration['size'] = $size;

        // Allow overwriting metadata on the fly
        foreach (['metadata', 'setMetadata'] as $key) {
            if (\is_array($configuration[$key] ?? null)) {
                $configuration[$key] = new Metadata($configuration[$key]);
            }
        }

        if (null === ($figure = $this->buildFigure($configuration))) {
            return null;
        }

        return $this->renderTemplate($figure, $template);
    }

    private function buildFigure(array $configuration): ?Figure
    {
        $figureBuilder = $this->studio->createFigureBuilder();

        foreach ($configuration as $property => $value) {
            $this->propertyAccessor->setValue($figureBuilder, $property, $value);
        }

        return $figureBuilder->buildIfResourceExists();
    }

    private function renderTemplate(Figure $figure, string $template): string
    {
        if (1 === preg_match('/\.html\.twig$/', $template)) {
            return $this->twig->render($template, ['figure' => $figure]);
        }

        if (1 !== preg_match('/^[^\/.\s]*$/', $template)) {
            throw new \InvalidArgumentException(sprintf('Invalid Contao template name "%s".', $template));
        }

        $imageTemplate = new FrontendTemplate($template);
        $figure->applyLegacyTemplateData($imageTemplate);

        return $imageTemplate->parse();
    }
}
