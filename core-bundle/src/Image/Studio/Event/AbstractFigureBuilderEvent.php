<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Image\Studio\Event;

use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\FigureBuilder;

abstract class AbstractFigureBuilderEvent
{
    /**
     * @var FigureBuilder
     */
    private $figureBuilder;

    /**
     * @var Figure
     */
    private $figure;

    public function __construct(FigureBuilder $figureBuilder, Figure $figure)
    {
        $this->figureBuilder = $figureBuilder;
        $this->figure = $figure;
    }

    public function getFigureBuilder(): FigureBuilder
    {
        return $this->figureBuilder;
    }

    public function getFigure(): Figure
    {
        return $this->figure;
    }
}
