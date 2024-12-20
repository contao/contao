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

#[AsCallback(table: 'tl_content', target: 'config.onpalette')]
class AccordionListener
{
    public function __invoke(string $palette, DataContainer $dc): string
    {
        $currentRecord = $dc->getCurrentRecord();

        if (!$currentRecord || 'tl_content' !== $currentRecord['ptable']) {
            return $palette;
        }

        $parentRecord = $dc->getCurrentRecord($currentRecord['pid'], 'tl_content');

        if (!$parentRecord || 'accordion' !== $parentRecord['type']) {
            return $palette;
        }

        return PaletteManipulator::create()
            ->addLegend('section_legend', 'type_legend', PaletteManipulator::POSITION_BEFORE)
            ->addField('sectionHeadline', 'section_legend', PaletteManipulator::POSITION_APPEND)
            ->applyToString($palette)
        ;
    }
}
