<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\ServiceAnnotation;

use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTagInterface;

abstract class AbstractFragmentAnnotation implements ServiceTagInterface
{
    private readonly array $attributes;

    public function __construct(array $attributes)
    {
        if (isset($attributes['value'])) {
            $attributes['type'] = $attributes['value'];
            unset($attributes['value']);
        }

        $this->attributes = $attributes;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
