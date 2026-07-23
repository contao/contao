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

use Contao\CoreBundle\String\SimpleTokenParser;
use Twig\Extension\RuntimeExtensionInterface;

final class SimpleTokenRuntime implements RuntimeExtensionInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly SimpleTokenParser $simpleTokenParser)
    {
    }

    public function parsePlain(\Stringable|string $content, array $tokens = []): string
    {
        return $this->simpleTokenParser->parse((string) $content, $tokens, false);
    }

    public function parseHtml(\Stringable|string $content, array $tokens = []): string
    {
        return $this->simpleTokenParser->parse((string) $content, $tokens, true);
    }
}
