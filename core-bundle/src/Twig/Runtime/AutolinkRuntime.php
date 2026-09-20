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

use Twig\Extension\RuntimeExtensionInterface;
use VStelmakh\UrlHighlight\Format;
use VStelmakh\UrlHighlight\Highlighter\CallbackHighlighter;
use VStelmakh\UrlHighlight\Url;
use VStelmakh\UrlHighlight\UrlHighlight;

final class AutolinkRuntime implements RuntimeExtensionInterface
{
    private readonly CallbackHighlighter $highlighter;

    /**
     * @internal
     */
    public function __construct(private readonly UrlHighlight $urlHighlight)
    {
        $this->highlighter = new CallbackHighlighter(static fn (Url $url) => \sprintf(
            '<a href="%s" target="_blank" rel="noreferrer noopener">%s</a>',
            htmlspecialchars($url->toHref('https')),
            htmlspecialchars($url->full),
        ));
    }

    public function linkUrls(string $text): string
    {
        return $this->urlHighlight->highlight($text, $this->highlighter, Format::HtmlEncoded);
    }
}
