<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Inspector;

/**
 * @experimental
 */
enum BlockType: string
{
    /**
     * The block was defined inside the template.
     */
    case origin = 'origin';

    /**
     * The block overwrites a block from another template: the parent() function is
     * not used.
     */
    case overwrite = 'overwrite';

    /**
     * The block enhances a block from another template: the parent() function is used.
     */
    case enhance = 'enhance';

    /**
     * The block was defined in another template and is not altered.
     */
    case transparent = 'transparent';
}
