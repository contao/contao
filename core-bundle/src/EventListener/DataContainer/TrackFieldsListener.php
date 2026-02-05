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
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Symfony\Component\Filesystem\Path;

class TrackFieldsListener
{
    #[AsCallback(table: 'tl_files', target: 'config.onpalette')]
    public function addTextTrackFields(string $palette, DataContainer $dc): string
    {
        // $dc->id is the file name in this case
        if ('vtt' === Path::getExtension($dc->id, true)) {
            $palette = PaletteManipulator::create()
                ->addField(['textTrackLanguage', 'textTrackType'], 'name')
                ->applyToString($palette)
            ;
        }

        return $palette;
    }
}
