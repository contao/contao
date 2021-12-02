<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class LegacyRoutingListener
{
    private ContaoFramework $framework;
    private TranslatorInterface $translator;
    private bool $prependLocale;
    private string $urlSuffix;

    public function __construct(ContaoFramework $framework, TranslatorInterface $translator, bool $prependLocale = false, string $urlSuffix = '.html')
    {
        $this->framework = $framework;
        $this->translator = $translator;
        $this->prependLocale = $prependLocale;
        $this->urlSuffix = $urlSuffix;
    }

    /**
     * @Callback(table="tl_page", target="config.onload")
     */
    public function disableRoutingFields(): void
    {
        $adapter = $this->framework->getAdapter(Image::class);

        $renderHelpIcon = fn () => $adapter->getHtml(
            'show.svg',
            '',
            sprintf(
                'title="%s"',
                StringUtil::specialchars($this->translator->trans('tl_page.legacyRouting', [], 'contao_tl_page'))
            )
        );

        foreach (['urlPrefix', 'urlSuffix', 'disableLanguageRedirect'] as $field) {
            $GLOBALS['TL_DCA']['tl_page']['fields'][$field]['eval']['disabled'] = true;
            $GLOBALS['TL_DCA']['tl_page']['fields'][$field]['eval']['helpwizard'] = false;
            $GLOBALS['TL_DCA']['tl_page']['fields'][$field]['xlabel'][] = $renderHelpIcon;
        }
    }

    /**
     * @Callback(table="tl_page", target="fields.urlPrefix.load")
     *
     * @param mixed $value
     */
    public function overrideUrlPrefix($value, DataContainer $dc): ?string
    {
        return $this->prependLocale ? LocaleUtil::formatAsLanguageTag($dc->activeRecord->language) : '';
    }

    /**
     * @Callback(table="tl_page", target="fields.urlSuffix.load")
     */
    public function overrideUrlSuffix(): string
    {
        return $this->urlSuffix;
    }
}
