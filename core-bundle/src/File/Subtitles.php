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

class Subtitles
{
    public function __construct(
        private readonly string $sourceLanguage,
        private readonly SubtitlesType|null $type,
    ) {
    }

    public function getSourceLanguage(): string
    {
        return $this->sourceLanguage;
    }

    public function getType(): SubtitlesType|null
    {
        return $this->type;
    }
}
