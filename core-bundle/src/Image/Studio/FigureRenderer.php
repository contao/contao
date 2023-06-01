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
    private readonly PropertyAccessor $propertyAccessor;

    public function __construct(
        private readonly Studio $studio,
        private readonly Environment $twig,
    ) {
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
    public function render(FilesModel|ImageInterface|int|string $from, PictureConfiguration|array|int|string|null $size, array $configuration = [], string $template = '@ContaoCore/Image/Studio/figure.html.twig'): string|null
    {
        if (null === ($figure = $this->buildFigure($from, $size, $configuration))) {
            return null;
        }

        return $this->renderTemplate($figure, $template);
    }

    /**
     * Builds a figure.
     *
     * The provided configuration array is used to configure a FigureBuilder
     * object.
     *
     * Returns null if the resource is invalid.
     *
     * @param int|string|FilesModel|ImageInterface       $from          Can be a FilesModel, an ImageInterface, a tl_files UUID/ID/path or a file system path
     * @param int|string|array|PictureConfiguration|null $size          A picture size configuration or reference
     * @param array<string, mixed>                       $configuration Configuration for the FigureBuilder
     */
    public function buildFigure(FilesModel|ImageInterface|int|string $from, PictureConfiguration|array|int|string|null $size, array $configuration = []): Figure|null
    {
        $configuration['from'] = $from;
        $configuration['size'] = $size;

        // Allow overwriting metadata on the fly
        foreach (['metadata', 'setMetadata'] as $key) {
            if (\is_array($configuration[$key] ?? null)) {
                $configuration[$key] = new Metadata($configuration[$key]);
            }
        }

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
