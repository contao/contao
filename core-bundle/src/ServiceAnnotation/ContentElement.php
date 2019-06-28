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

use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Doctrine\Common\Annotations\Annotation\Attribute;
use Doctrine\Common\Annotations\Annotation\Attributes;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Annotation that can be used to define controller as a content element.
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * @Attributes({
 *     @Attribute("type", type = "string"),
 *     @Attribute("category", required = true, type = "string"),
 *     @Attribute("template", type = "string"),
 *     @Attribute("renderer", type = "string"),
 *     @Attribute("attributes", type = "array"),
 * })
 */
final class ContentElement extends AbstractFragmentAnnotation
{
    public function __construct(array $values)
    {
        parent::__construct($values);

        $this->name = ContentElementReference::TAG_NAME;
    }
}
