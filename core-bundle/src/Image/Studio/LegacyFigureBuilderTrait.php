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

use Contao\System;

/**
 * This trait simplifies the FigureBuilder usage in legacy content elements and
 * front end modules where dependency injection is not available and missing
 * images are silently ignored to remain backwards compatible.
 *
 * @internal
 */
trait LegacyFigureBuilderTrait
{
    private function getFigureBuilder(): FigureBuilder
    {
        return System::getContainer()->get(Studio::class)->createFigureBuilder();
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

        $figureBuilder = $this->getFigureBuilder()->from($resource);

        if (null !== $figureBuilder->getLastException()) {
            return null;
        }

        return $figureBuilder;
    }
}
