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

use Contao\ContentGallery;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;

/**
 * Adjusts the palette for the "gallery" content element when the legacy version
 * is in use.
 */
#[AsCallback('tl_content', 'config.onload')]
class LegacyGalleryPaletteListener
{
    public function __invoke(): void
    {
        if (!is_a($GLOBALS['TL_CTE']['media']['gallery'] ?? null, ContentGallery::class, true)) {
            return;
        }

        PaletteManipulator::create()
            ->addField('galleryTpl', 'customTpl', PaletteManipulator::POSITION_BEFORE)
            ->applyToPalette('gallery', 'tl_content')
        ;
    }
}
