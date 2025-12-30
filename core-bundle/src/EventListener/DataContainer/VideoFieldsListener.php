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
use Symfony\Component\Mime\MimeTypes;

readonly class VideoFieldsListener
{
    public function __construct(private MimeTypes $mimeTypes)
    {
    }

    #[AsCallback(table: 'tl_files', target: 'config.onpalette')]
    public function addVideoFields(string $palette, DataContainer $dc): string
    {
        $mime = $this->mimeTypes->guessMimeType($dc->id);

        if ($mime !== null && str_starts_with($mime, 'video/')) {
            $palette = PaletteManipulator::create()
                ->addField('videoSizes', 'name')
                ->applyToString($palette)
            ;
        }

        return $palette;
    }
}
