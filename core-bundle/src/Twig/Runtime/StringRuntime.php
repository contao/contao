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
use Contao\CoreBundle\String\HtmlDecoder;
use Contao\StringUtil;
use Twig\Extension\RuntimeExtensionInterface;

final class StringRuntime implements RuntimeExtensionInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly HtmlDecoder $htmlDecoder,
    ) {
    }

    public function encodeEmail(string $html): string
    {
        $this->framework->initialize();

        return $this->framework->getAdapter(StringUtil::class)->encodeEmail($html);
    }

    public function inputEncodedToPlainText(string $value, bool $removeInsertTags = false): string
    {
        return $this->htmlDecoder->inputEncodedToPlainText($value, $removeInsertTags);
    }

    public function rawHtmlToPlainText(string $value, bool $removeInsertTags = false): string
    {
        return $this->htmlDecoder->htmlToPlainText($value, $removeInsertTags);
    }
}
