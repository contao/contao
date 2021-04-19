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

class BuildFigureEvent
{
    /**
     * @var Figure
     */
    private $figure;

    public function __construct(Figure $figure)
    {
        $this->figure = $figure;
    }

    public function getFigure(): Figure
    {
        return $this->figure;
    }
}
