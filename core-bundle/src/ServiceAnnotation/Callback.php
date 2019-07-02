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
use Doctrine\Common\Annotations\AnnotationException;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;

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
final class Callback extends ServiceTag
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
        parent::__construct([]);

        if (empty($values['table'])) {
            throw AnnotationException::typeError('Attribute "table" of @'.static::class.' should not be null.');
        }

        if (empty($values['target'])) {
            throw AnnotationException::typeError('Attribute "target" of @'.static::class.' should not be null.');
        }

        $this->name = 'contao.callback';
        $this->table = $values['table'];
        $this->target = $values['target'];
        $this->priority = $values['priority'] ?? null;
    }

    public function getAttributes(): array
    {
        $attributes = [
            'table' => $this->table,
            'target' => $this->target,
        ];

        if ($this->priority) {
            $attributes['priority'] = $this->priority;
        }

        return $attributes;
    }
}
