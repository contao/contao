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

enum SubtitleType
{
    case subtitles;
    case captions;
    case descriptions;
    case chapters;
    case metadata;
}
