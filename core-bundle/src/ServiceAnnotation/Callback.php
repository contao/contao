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

/**
 * Annotation that can be used to register a DCA callback.
 *
 * @Annotation
 * @Target({"METHOD"})
 * @Attributes({
 *     @Attribute("table", type="string", required=true),
 *     @Attribute("target", type="string", required=true),
 *     @Attribute("priority", type="int"),
 * })
 */
final class Callback extends AbstractFragmentAnnotation
{
    /**
     * @var string
     */
    private $table;

    /**
     * @var string
     */
    private $target;

    /**
     * @var int|null
     */
    private $priority;

    public function __construct(array $values)
    {
        parent::__construct($values);

        $this->name = 'contao.callback';
        $this->table = $values['table'] ?? null;
        $this->target = $values['target'] ?? null;
        $this->priority = $values['priority'] ?? null;
    }

    public function getAttributes(): array
    {
        $attributes = parent::getAttributes();
        $attributes['table'] = $this->table;
        $attributes['target'] = $this->target;

        if ($this->priority) {
            $attributes['priority'] = $this->priority;
        }

        return $attributes;
    }
}
