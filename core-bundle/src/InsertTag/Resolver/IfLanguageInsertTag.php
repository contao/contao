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

use Contao\CoreBundle\DependencyInjection\Attribute\AsBlockInsertTag;
use Contao\CoreBundle\InsertTag\Exception\InvalidInsertTagException;
use Contao\CoreBundle\InsertTag\ParsedSequence;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\StringUtil;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsBlockInsertTag('iflng', endTag: 'iflng')]
#[AsBlockInsertTag('ifnlng', endTag: 'ifnlng')]
class IfLanguageInsertTag implements BlockInsertTagResolverNestedResolvedInterface
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function __invoke(ResolvedInsertTag $insertTag, ParsedSequence $wrappedContent): ParsedSequence
    {
        $inverse = 'iflng' !== $insertTag->getName();

        if (!$language = $insertTag->getParameters()->get(0)) {
            throw new InvalidInsertTagException(\sprintf('Missing language parameter in %s insert tag', $insertTag->getName()));
        }

        if ($this->languageMatchesTranslatorLocale($language)) {
            return $inverse ? new ParsedSequence([]) : $wrappedContent;
        }

        return $inverse ? $wrappedContent : new ParsedSequence([]);
    }

    private function languageMatchesTranslatorLocale(string $language): bool
    {
        $pageLanguage = LocaleUtil::formatAsLocale($this->translator->getLocale());

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
