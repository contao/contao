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

use Contao\CoreBundle\Fragment\Reference\FrontendModuleReference;
use Doctrine\Common\Annotations\Annotation\Attribute;
use Doctrine\Common\Annotations\Annotation\Attributes;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Annotation to define a controller as frontend module.
 *
 * @Annotation
 *
 * @Target({"CLASS", "METHOD"})
 *
 * @Attributes({
 *     @Attribute("value", type="string"),
 *     @Attribute("category", type="string", required = true),
 *     @Attribute("template", type="string"),
 *     @Attribute("renderer", type="string"),
 * })
 */
final class FrontendModule extends AbstractFragmentAnnotation
{
    public function getName(): string
    {
        return FrontendModuleReference::TAG_NAME;
    }
}
