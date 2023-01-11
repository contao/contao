<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem;

enum SortMode: string
{
    case pathAscending = 'path_asc';
    case pathDescending = 'path_desc';
    case pathNaturalAscending = 'name_asc';
    case pathNaturalDescending = 'name_desc';
    case lastModifiedAscending = 'date_asc';
    case lastModifiedDescending = 'date_desc';
}
