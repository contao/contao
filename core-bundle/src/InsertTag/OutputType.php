<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\InsertTag;

enum OutputType: string
{
    case text = 'text';
    case html = 'html';
    case js = 'js';
    case css = 'css';
    case url = 'url';
}
