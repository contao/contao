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

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\Image;
use Contao\StringUtil;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\ServiceAnnotationBundle\ServiceAnnotationInterface;

/**
 * @Callback(table="tl_settings", target="config.onload")
 */
class DisableBundleConfiguredSettingsListener implements ServiceAnnotationInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * Local config setting defined by parameter.
     *
     * @var array
     */
    private $localConfig;

    public function __construct(TranslatorInterface $translator, ContaoFrameworkInterface $framework, array $localConfig)
    {
        $this->translator = $translator;
        $this->framework = $framework;
        $this->localConfig = $localConfig;
    }

    public function onLoadCallback(): void
    {
        foreach (array_keys($this->localConfig) as $field) {
            if (!isset($GLOBALS['TL_DCA']['tl_settings']['fields'][$field])) {
                continue;
            }

            $GLOBALS['TL_DCA']['tl_settings']['fields'][$field]['eval']['disabled'] = true;
            $GLOBALS['TL_DCA']['tl_settings']['fields'][$field]['eval']['helpwizard'] = false;
            $GLOBALS['TL_DCA']['tl_settings']['fields'][$field]['xlabel'][] = [self::class, 'renderHelpIcon'];
        }
    }

    public function renderHelpIcon(): string
    {
        $adapter = $this->framework->getAdapter(Image::class);

        return $adapter->getHtml(
            'important.svg',
            $this->translator->trans('tl_settings.configuredInBundle.0', [], 'contao_tl_settings'),
            sprintf(
                'title="%s"',
                StringUtil::specialchars(
                    $this->translator->trans('tl_settings.configuredInBundle.1', [], 'contao_tl_settings')
                )
            )
        );
    }
}
