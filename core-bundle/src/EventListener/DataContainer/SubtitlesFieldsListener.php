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
use Contao\CoreBundle\File\SubtitlesType;
use Contao\DataContainer;

class SubtitlesFieldsListener
{
    #[AsCallback(table: 'tl_files', target: 'config.onpalette')]
    public function addSubtitlesFields(string $palette, DataContainer $dc): string
    {
        // $dc->id is the file name in this case
        if (str_ends_with($dc->id, '.vtt')) {
            $palette = PaletteManipulator::create()
                ->addField(['subtitlesLanguage', 'subtitlesType'], 'name')
                ->applyToString($palette)
            ;
        }

        return $palette;
    }

    #[AsCallback(table: 'tl_files', target: 'fields.subtitlesType.options')]
    public function subtitlesTypeOptions(): array
    {
        return array_map(static fn ($case) => $case->name, SubtitlesType::cases());
    }
}
