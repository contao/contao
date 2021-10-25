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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Twig\Interop\ChunkedText;
use Contao\InsertTags;
use Twig\Extension\RuntimeExtensionInterface;

final class InsertTagRuntime implements RuntimeExtensionInterface
{
    private ContaoFramework $framework;

    /**
     * @internal
     */
    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    public function renderInsertTag(string $insertTag): string
    {
        return $this->replaceInsertTags('{{'.$insertTag.'}}');
    }

    public function replaceInsertTags(string $text): string
    {
        /** @var InsertTags $insertTags */
        $insertTags = $this->framework->getAdapter(InsertTags::class);

        return $insertTags->replace($text, false);
    }

    public function replaceInsertTagsChunkedRaw(string $text): ChunkedText
    {
        /** @var InsertTags $insertTags */
        $insertTags = $this->framework->getAdapter(InsertTags::class);

        return new ChunkedText($insertTags->replace($text, false, true));
    }
}
