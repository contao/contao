<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fragment\Annotation;

abstract class AbstractFragmentAnnotation
{
    /**
     * @var string
     */
    public $category;

    /**
     * @var array
     */
    public $options = [];

    /**
     * @var string|null
     */
    public $renderer;

    /**
     * @var string|null
     */
    public $service;

    /**
     * @var string|null
     */
    public $template;

    /**
     * @var string|null
     */
    public $type;

    /**
     * Annotation constructor.
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->category = $values['category'];
        $this->options = $values['options'] ?? [];
        $this->service = $values['service'] ?? null;
        $this->renderer = $values['renderer'] ?? null;
        $this->template = $values['template'] ?? null;
        $this->type = $values['type'] ?? null;
    }
}
