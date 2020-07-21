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
use Terminal42\ServiceAnnotationBundle\ServiceAnnotationInterface;

/**
 * Annotation class for @Page().
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * @Attributes({
 *     @Attribute("value", type = "string"),
 *     @Attribute("path", type = "string"),
 *     @Attribute("urlSuffix", type = "string"),
 *     @Attribute("requirements", type = "array"),
 *     @Attribute("options", type = "array"),
 *     @Attribute("defaults", type = "array"),
 *     @Attribute("methods", type = "array"),
 *     @Attribute("contentComposition", type = "boolean"),
 * })
 */
final class Page implements ServiceAnnotationInterface
{
    /**
     * @var array
     */
    private $attributes;

    public function __construct(array $attributes)
    {
        if (isset($attributes['value'])) {
            $attributes['type'] = $attributes['value'];
            unset($attributes['value']);
        }

        $this->attributes = $attributes;
    }

    public function getName(): string
    {
        return 'contao.page';
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
