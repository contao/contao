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
 *
 * @deprecated Deprecated since Contao 5.4, to be removed in Contao 6;
 *             use the #[AsPickerProvider] attribute instead
 */
final class PickerProvider implements ServiceTagInterface
{
    public int|null $priority = null;

    public function getName(): string
    {
        trigger_deprecation('contao/core-bundle', '5.4', 'Using the @PickerProvider annotation has been deprecated and will no longer work in Contao 6. Use the #[AsPickerProvider] attribute instead.');

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
