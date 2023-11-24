<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Runtime;

use Highlight\Highlighter;
use Twig\Extension\RuntimeExtensionInterface;

final class HighlighterRuntime implements RuntimeExtensionInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly Highlighter $highlighter = new Highlighter())
    {
    }

    public function highlight(string $code, string|null $languageName = null): HighlightResult
    {
        $languageName = match ($languageName) {
            'C#' => 'csharp',
            'C++' => 'cpp',
            '', null => 'plaintext',
            default => strtolower($languageName),
        };

        return new HighlightResult($this->highlighter->highlight($languageName, $code));
    }

    /**
     * @param array<string>|null $languageSubset
     */
    public function highlightAuto(string $code, array|null $languageSubset = null): HighlightResult
    {
        return new HighlightResult($this->highlighter->highlightAuto($code, $languageSubset));
    }
}
