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
use Contao\Image;
use Contao\StringUtil;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class RelCanonicalListener
{
    private ContaoFramework $framework;
    private TranslatorInterface $translator;

    public function __construct(ContaoFramework $framework, TranslatorInterface $translator)
    {
        $this->framework = $framework;
        $this->translator = $translator;
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
                StringUtil::specialchars($this->translator->trans('tl_page.relCanonical', [], 'contao_tl_page'))
            )
        );

        foreach (['canonicalLink', 'canonicalKeepParams'] as $field) {
            $GLOBALS['TL_DCA']['tl_page']['fields'][$field]['eval']['disabled'] = true;
            $GLOBALS['TL_DCA']['tl_page']['fields'][$field]['eval']['helpwizard'] = false;
            $GLOBALS['TL_DCA']['tl_page']['fields'][$field]['xlabel'][] = $renderHelpIcon;
        }
    }
}
