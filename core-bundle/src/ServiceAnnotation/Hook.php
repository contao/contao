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
 * Annotation that can be used to register a Contao hook.
 *
 * @Annotation
 * @Target({"METHOD"})
 * @Attributes({
 *     @Attribute("hook", type="string", required=true),
 *     @Attribute("priority", type="int"),
 * })
 */
final class Hook extends ServiceTag
{
    /**
     * @var string
     */
    private $hook;

    /**
     * @var int|null
     */
    private $priority;

    public function __construct(array $values)
    {
        parent::__construct($values);

        $this->name = 'contao.hook';
        $this->hook = $values['hook'] ?? null;
        $this->priority = $values['priority'] ?? null;
    }

    public function getAttributes(): array
    {
        $attributes = parent::getAttributes();
        $attributes['hook'] = $this->hook;

        if ($this->priority) {
            $attributes['priority'] = $this->priority;
        }

        return $attributes;
    }
}
