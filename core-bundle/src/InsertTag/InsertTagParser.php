<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\InsertTag;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\InsertTags;
use Symfony\Contracts\Service\ResetInterface;

class InsertTagParser implements ResetInterface
{
    public function __construct(private ContaoFramework $framework, private InsertTags|null $insertTags = null)
    {
    }

    public function replace(string $input): string
    {
        return (string) $this->replaceChunked($input);
    }

    public function replaceChunked(string $input): ChunkedText
    {
        return $this->callLegacyClass($input, true);
    }

    public function replaceInline(string $input): string
    {
        return (string) $this->replaceInlineChunked($input);
    }

    public function replaceInlineChunked(string $input): ChunkedText
    {
        return $this->callLegacyClass($input, false);
    }

    public function render(string $input): string
    {
        $chunked = iterator_to_array($this->replaceInlineChunked('{{'.$input.'}}'));

        if (!$chunked) {
            return '';
        }

        if (1 !== \count($chunked) || ChunkedText::TYPE_RAW !== $chunked[0][0] || !\is_string($chunked[0][1])) {
            throw new \RuntimeException('Rendering a single insert tag has to return a single raw chunk');
        }

        return $chunked[0][1];
    }

    public function reset(): void
    {
        InsertTags::reset();
    }

    private function callLegacyClass(string $input, bool $allowEsiTags): ChunkedText
    {
        if (null === $this->insertTags) {
            $this->framework->initialize();
            $this->insertTags = new InsertTags();
        }

        return $this->insertTags->replaceInternal($input, $allowEsiTags);
    }
}
