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

use Doctrine\Common\Annotations\Annotation\Attribute;
use Doctrine\Common\Annotations\Annotation\Attributes;
use Doctrine\Common\Annotations\Annotation\Target;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;

/**
 * Annotation that can be used to register a Contao picker.
 *
 * @Annotation
 * @Target({"CLASS"})
 * @Attributes({
 *     @Attribute("priority", type="int"),
 * })
 */
final class PickerProvider extends ServiceTag
{
    /**
     * @var int|null
     */
    private $priority;

    public function __construct(array $values)
    {
        parent::__construct($values);

        $this->name = 'contao.picker_provider';
        $this->priority = $values['priority'] ?? null;
    }

    public function getAttributes(): array
    {
        $attributes = parent::getAttributes();

        if ($this->priority) {
            $attributes['priority'] = $this->priority;
        }

        return $attributes;
    }
}
