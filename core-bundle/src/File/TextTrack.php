<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\File;

readonly class TextTrack
{
    public function __construct(
        private string $sourceLanguage,
        private TextTrackType|null $type,
    ) {
    }

    public function getSourceLanguage(): string
    {
        return $this->sourceLanguage;
    }

    public function getType(): TextTrackType|null
    {
        return $this->type;
    }
}
