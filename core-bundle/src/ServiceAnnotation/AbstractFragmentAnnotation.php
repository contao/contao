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

use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;

abstract class AbstractFragmentAnnotation extends ServiceTag
{
    /**
     * @var string|null
     */
    protected $type;

    /**
     * @var string
     */
    protected $category;

    /**
     * @var string|null
     */
    protected $renderer;

    /**
     * @var string|null
     */
    protected $template;

    public function __construct(array $values)
    {
        parent::__construct($values);

        $this->type = $values['type'] ?? null;
        $this->category = $values['category'];
        $this->template = $values['template'] ?? null;
        $this->renderer = $values['renderer'] ?? null;
    }

    public function getAttributes(): array
    {
        $attributes = parent::getAttributes();
        $attributes['category'] = $this->category;

        if ($this->type) {
            $attributes['type'] = $this->type;
        }

        if ($this->renderer) {
            $attributes['renderer'] = $this->renderer;
        }

        if ($this->template) {
            $attributes['template'] = $this->template;
        }

        return $attributes;
    }
}
