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
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTagInterface;

/**
 * Annotation to register a Contao picker.
 *
 * @Annotation
 *
 * @Target({"CLASS"})
 *
 * @Attributes({
 *     @Attribute("priority", type="int"),
 * })
 */
final class PickerProvider implements ServiceTagInterface
{
    public int|null $priority = null;

    public function getName(): string
    {
        return 'contao.picker_provider';
    }

    public function getAttributes(): array
    {
        if (null !== $this->priority) {
            return ['priority' => $this->priority];
        }

        return [];
    }
}
