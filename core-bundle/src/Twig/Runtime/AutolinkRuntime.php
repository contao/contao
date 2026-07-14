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
use VStelmakh\UrlHighlight\UrlHighlight;

final class AutolinkRuntime implements RuntimeExtensionInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly UrlHighlight $urlHighlight)
    {
    }

    public function linkUrls(string $text): string
    {
        return $this->urlHighlight->highlightUrls($text);
    }
}
