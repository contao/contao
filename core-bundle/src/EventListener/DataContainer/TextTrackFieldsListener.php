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
use Contao\CoreBundle\File\TextTrackType;
use Contao\DataContainer;

class TextTrackFieldsListener
{
    #[AsCallback(table: 'tl_files', target: 'config.onpalette')]
    public function addTextTrackFields(string $palette, DataContainer $dc): string
    {
        // $dc->id is the file name in this case
        if (str_ends_with($dc->id, '.vtt')) {
            $palette = PaletteManipulator::create()
                ->addField(['textTrackLanguage', 'textTrackType'], 'name')
                ->applyToString($palette)
            ;
        }

        return $palette;
    }
}
