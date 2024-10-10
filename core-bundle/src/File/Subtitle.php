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

class Subtitle
{
    public function __construct(
        private readonly ?string $sourceLanguage,
        private readonly ?string $type,
    ) {
    }

    public function getSourceLanguage(): string
    {
        return $this->sourceLanguage;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
