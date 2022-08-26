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
use Contao\DataContainer;
use Contao\Image;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Callback(table="tl_page", target="fields.canonicalLink.load")
 * @Callback(table="tl_page", target="fields.canonicalKeepParams.load")
 */
class DisableCanonicalFieldsListener
{
    private ContaoFramework $framework;
    private TranslatorInterface $translator;

    public function __construct(ContaoFramework $framework, TranslatorInterface $translator)
    {
        $this->framework = $framework;
        $this->translator = $translator;
    }

    public function __invoke(string $value, DataContainer $dc): string
    {
        if (!$dc->id) {
            return $value;
        }

        $adapter = $this->framework->getAdapter(PageModel::class);

        if (!($page = $adapter->findWithDetails($dc->id)) || $page->enableCanonical) {
            return $value;
        }

        $adapter = $this->framework->getAdapter(Image::class);

        $renderHelpIcon = fn () => $adapter->getHtml(
            'show.svg',
            '',
            sprintf(
                'title="%s"',
                StringUtil::specialchars($this->translator->trans('tl_page.relCanonical', [], 'contao_tl_page'))
            )
        );

        $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['disabled'] = true;
        $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['helpwizard'] = false;
        $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['xlabel'][] = $renderHelpIcon;

        return $value;
    }
}
