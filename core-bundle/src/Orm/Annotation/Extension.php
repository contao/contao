<?php

declare(strict_types=1);

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
