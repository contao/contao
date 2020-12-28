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

use Contao\CoreBundle\Exception\InvalidResourceException;
use Contao\System;

/**
 * This trait simplifies the FigureBuilder usage in legacy content elements and
 * modules where dependency injection isn't available and missing images are
 * silently ignored for BC reasons.
 *
 * @internal
 */
trait LegacyFigureBuilderTrait
{
    private function getFigureBuilder(): FigureBuilder
    {
        /** @var Studio $studio */
        $studio = System::getContainer()->get(Studio::class);

        return $studio->createFigureBuilder();
    }

    /**
     * Returns a FigureBuilder configured to use the given resource or null if
     * the resource is invalid.
     */
    private function getFigureBuilderIfResourceExists($resource): ?FigureBuilder
    {
        if (empty($resource)) {
            return null;
        }

        $figureBuilder = $this->getFigureBuilder();

        try {
            $figureBuilder->from($resource);
        } catch (InvalidResourceException $e) {
            return null;
        }

        return $figureBuilder;
    }
}
