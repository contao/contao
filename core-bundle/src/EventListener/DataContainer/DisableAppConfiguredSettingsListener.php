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

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Image;
use Contao\StringUtil;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCallback(table: "tl_settings", target: "config.onload")]
class DisableAppConfiguredSettingsListener
{
    public function __construct(private TranslatorInterface $translator, private ContaoFramework $framework, private array $localConfig)
    {
    }

    public function onLoadCallback(): void
    {
        foreach (array_keys($this->localConfig) as $field) {
            if (!isset($GLOBALS['TL_DCA']['tl_settings']['fields'][$field])) {
                continue;
            }

            $GLOBALS['TL_DCA']['tl_settings']['fields'][$field]['xlabel'][] = [
                'contao.listener.data_container.disable_app_configured_settings',
                'renderHelpIcon',
            ];

            $GLOBALS['TL_DCA']['tl_settings']['fields'][$field]['eval']['disabled'] = true;
            $GLOBALS['TL_DCA']['tl_settings']['fields'][$field]['eval']['helpwizard'] = false;
            $GLOBALS['TL_DCA']['tl_settings']['fields'][$field]['eval']['chosen'] = false;
        }
    }

    public function renderHelpIcon(): string
    {
        $adapter = $this->framework->getAdapter(Image::class);

        return $adapter->getHtml(
            'show.svg',
            '',
            sprintf(
                'title="%s"',
                StringUtil::specialchars($this->translator->trans('tl_settings.configuredInApp', [], 'contao_tl_settings'))
            )
        );
    }
}
