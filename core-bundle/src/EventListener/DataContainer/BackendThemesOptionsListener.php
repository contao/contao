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
    private array $backendConfig;

    public function __construct(BackendThemes $backendThemes, array $backendConfig)
    {
        $this->backendThemes = $backendThemes;
        $this->backendConfig = $backendConfig;
    }

    /**
     * @Callback(table="tl_user", target="fields.backendTheme.options")
     */
    public function options(DataContainer $dc): array
    {
        $themes = $this->backendThemes->getThemeNames();
        $themes = array_merge($themes, array_values(Backend::getThemes()));

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

        if (null === $this->backendConfig['theme']) {
            return;
        }

        $GLOBALS['TL_DCA']['tl_user']['fields']['backendTheme']['eval']['disabled'] = true;
    }
}
