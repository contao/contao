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

abstract class Base
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
     * @var string
     */
    public $renderer;

    /**
     * @var string
     */
    public $service;

    /**
     * @var string
     */
    public $template;

    /**
     * @var string
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
        $this->options = (array) $values['options'];
        $this->service = $values['service'];
        $this->renderer = $values['renderer'];
        $this->template = $values['template'];
        $this->type = $values['type'];
    }
}
