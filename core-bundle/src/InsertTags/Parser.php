<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\InsertTags;

use Contao\CoreBundle\Twig\Interop\ChunkedText;
use Contao\InsertTags;
use Symfony\Contracts\Service\ResetInterface;

class Parser implements ResetInterface
{
    private InsertTags $insertTags;

    public function __construct(InsertTags $insertTags = null)
    {
        $this->insertTags = $insertTags ?? new InsertTags();
    }

    public function replace(string $input): string
    {
        return (string) $this->replaceChunked($input);
    }

    public function replaceChunked(string $input): ChunkedText
    {
        return $this->insertTags->replace($input, true, true);
    }

    public function render(string $input): string
    {
        $chunked = iterator_to_array($this->insertTags->replace('{{'.$input.'}}', false, true));

        if (1 !== \count($chunked) || ChunkedText::TYPE_RAW !== $chunked[0][0] || !\is_string($chunked[0][1])) {
            throw new \RuntimeException('Rendering a single insert tag has to return a single raw chunk');
        }

        return $chunked[0][1];
    }

    public function reset(): void
    {
        $this->insertTags::reset();
    }
}
