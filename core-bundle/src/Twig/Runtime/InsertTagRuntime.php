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

use Contao\CoreBundle\Twig\Interop\ChunkedText;
use Contao\InsertTags;
use Twig\Extension\RuntimeExtensionInterface;

final class InsertTagRuntime implements RuntimeExtensionInterface
{
    private InsertTags $insertTags;

    /**
     * @internal
     */
    public function __construct(InsertTags $insertTags = null)
    {
        $this->insertTags = $insertTags ?? new InsertTags();
    }

    public function renderInsertTag(string $insertTag): string
    {
        return $this->replaceInsertTags('{{'.$insertTag.'}}');
    }

    public function replaceInsertTags(string $text): string
    {
        return $this->insertTags->replace($text, false);
    }

    public function replaceInsertTagsChunkedRaw(string $text): ChunkedText
    {
        return $this->insertTags->replace($text, false, true);
    }
}
