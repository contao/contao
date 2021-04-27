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

use Contao\CoreBundle\Image\Studio\MediaResultInterface;

class DefineMediaResultEvent extends AbstractFigureBuilderEvent
{
    /**
     * @var MediaResultInterface|null
     */
    private $mediaResult;

    public function setMediaResult(MediaResultInterface $mediaResult): void
    {
        $this->mediaResult = $mediaResult;
    }

    public function getMediaResult(): ?MediaResultInterface
    {
        return $this->mediaResult;
    }
}
