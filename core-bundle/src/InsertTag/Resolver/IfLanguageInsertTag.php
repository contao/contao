<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\InsertTag\Resolver;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\Exception\InvalidInsertTagException;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\InsertTag\ParsedInsertTag;
use Contao\CoreBundle\InsertTag\ParsedSequence;
use Contao\CoreBundle\InsertTag\ProcessingMode;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\StringUtil;

class IfLanguageInsertTag
{
    public function __construct(private InsertTagParser $parser)
    {
    }

    #[AsInsertTag('iflng', endTag: 'iflng', mode: ProcessingMode::wrappedParsed)]
    #[AsInsertTag('ifnlng', endTag: 'ifnlng', mode: ProcessingMode::wrappedParsed)]
    public function replaceInsertTag(ParsedInsertTag $insertTag, ParsedSequence $wrappedContent): ParsedSequence
    {
        $inverse = 'iflng' !== $insertTag->getName();
        $language =
            $this->parser->replaceInline($insertTag->getParameters()->get(0) ?? '')
            ?: throw new InvalidInsertTagException(sprintf('Missing language parameter in %s insert tag', $insertTag->getName()))
        ;

        if ($this->languageMatchesPage($language)) {
            return $inverse ? new ParsedSequence([]) : $wrappedContent;
        }

        return $inverse ? $wrappedContent : new ParsedSequence([]);
    }

    private function languageMatchesPage(string $language): bool
    {
        $pageLanguage = LocaleUtil::formatAsLocale($GLOBALS['objPage']?->language ?? '');

        foreach (StringUtil::trimsplit(',', $language) as $lang) {
            if ($pageLanguage === LocaleUtil::formatAsLocale($lang)) {
                return true;
            }

            if (str_ends_with($lang, '*') && 0 === strncmp($pageLanguage, $lang, \strlen((string) $lang) - 1)) {
                return true;
            }
        }

        return false;
    }
}
