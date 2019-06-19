<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fragment\Annotations;

use Doctrine\Common\Annotations\Annotation\Attribute;
use Doctrine\Common\Annotations\Annotation\Attributes;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Annotation that can be used to define controller as a content element.
 *
 * @Annotation
 * @Target("CLASS", "METHOD")
 * @Attributes({
 *     @Attribute("category", required = true, type = "string"),
 *     @Attribute("options", type = "array"),
 *     @Attribute("renderer", type = "string"),
 *     @Attribute("service", type = "string"),
 *     @Attribute("template", type = "string"),
 *     @Attribute("type", type = "string"),
 * })
 */
final class ContentElement
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
