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

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\ServiceAnnotationBundle\ServiceAnnotationInterface;

class LegacyRoutingListener implements ServiceAnnotationInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var bool
     */
    private $prependLocale;

    /**
     * @var string
     */
    private $urlSuffix;

    public function __construct(TranslatorInterface $translator, bool $prependLocale = false, string $urlSuffix = '.html')
    {
        $this->translator = $translator;
        $this->prependLocale = $prependLocale;
        $this->urlSuffix = $urlSuffix;
    }

    /**
     * @Callback(table="tl_page", target="config.onload")
     */
    public function disableRoutingFields(): void
    {
        $translator = $this->translator;

        $GLOBALS['TL_DCA']['tl_page']['fields']['languagePrefix']['eval']['disabled'] = true;
        $GLOBALS['TL_DCA']['tl_page']['fields']['urlSuffix']['eval']['disabled'] = true;

        $GLOBALS['TL_DCA']['tl_page']['fields']['legacy_routing'] = [
            'input_field_callback' => static function () use ($translator) {
                return sprintf(
                    '<p class="tl_gerror">%s</p>',
                    $translator->trans('tl_page.legacyRouting', [], 'contao_tl_page')
                );
            },
        ];

        PaletteManipulator::create()
            ->addField('legacy_routing', 'url_legend', PaletteManipulator::POSITION_PREPEND)
            ->applyToPalette('root', 'tl_page')
            ->applyToPalette('rootfallback', 'tl_page')
        ;
    }

    /**
     * @Callback(table="tl_page", target="fields.languagePrefix.load")
     */
    public function overrideLanguagePrefix($value, DataContainer $dc)
    {
        return $this->prependLocale ? $dc->activeRecord->language : '';
    }

    /**
     * @Callback(table="tl_page", target="fields.urlSuffix.load")
     */
    public function overrideUrlSuffix($value)
    {
        return $this->urlSuffix;
    }
}
