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

use Contao\Backend;
use Contao\CoreBundle\BackendTheme\BackendThemes;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;

/**
 * @internal
 */
class BackendThemesOptionsListener
{
    private BackendThemes $backendThemes;
    private ?string $backendTheme;

    public function __construct(BackendThemes $backendThemes, ?string $backendTheme)
    {
        $this->backendThemes = $backendThemes;
        $this->backendTheme = $backendTheme;
    }

    /**
     * @Callback(table="tl_user", target="fields.backendTheme.options")
     */
    public function options(DataContainer $dc): array
    {
        $themes = $this->backendThemes->getThemeNames();

        return array_combine($themes, $themes);
    }

    /**
     * @Callback(table="tl_user", target="config.onload")
     */
    public function onload($dc): void
    {
        if (!$dc instanceof DataContainer) {
            return;
        }

        if ('' === (string) $this->backendTheme) {
            return;
        }

        $GLOBALS['TL_DCA']['tl_user']['fields']['backendTheme']['eval']['disabled'] = true;
    }
}
