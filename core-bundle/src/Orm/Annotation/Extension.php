<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Orm\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Extension
{
    /**
     * @var string
     */
    public $index;

    /**
     * @var array
     */
    public $indexes = [];
}
