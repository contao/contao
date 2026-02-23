<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\File;

enum TextTrackType: string
{
    case captions = 'captions';
    case descriptions = 'descriptions';
    case subtitles = 'subtitles';
    case chapters = 'chapters';
    case metadata = 'metadata';
}
