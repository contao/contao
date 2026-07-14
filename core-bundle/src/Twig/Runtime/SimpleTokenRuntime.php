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

use Contao\CoreBundle\InsertTag\ChunkedText;
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

    /**
     * @return ($content is ChunkedText ? ChunkedText : string)
     */
    public function parsePlain(ChunkedText|\Stringable|string $content, array $tokens = []): ChunkedText|string
    {
        return $this->parse($content, $tokens, false);
    }

    /**
     * @return ($content is ChunkedText ? ChunkedText : string)
     */
    public function parseHtml(ChunkedText|\Stringable|string $content, array $tokens = []): ChunkedText|string
    {
        return $this->parse($content, $tokens, true);
    }

    /**
     * @return ($content is ChunkedText ? ChunkedText : string)
     */
    private function parse(ChunkedText|\Stringable|string $content, array $tokens, bool $asHtml): ChunkedText|string
    {
        if ($content instanceof ChunkedText) {
            $chunks = [];

            foreach ($content as [$type, $chunk]) {
                $chunks[] = [$type, $this->simpleTokenParser->parse($chunk, $tokens, $asHtml || ChunkedText::TYPE_RAW === $type)];
            }

            return ChunkedText::fromTypedChunks($chunks);
        }

        return $this->simpleTokenParser->parse((string) $content, $tokens, $asHtml);
    }
}
