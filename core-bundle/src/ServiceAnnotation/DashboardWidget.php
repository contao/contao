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

use Contao\CoreBundle\Fragment\Reference\DashboardWidgetReference;
use Doctrine\Common\Annotations\Annotation\Attribute;
use Doctrine\Common\Annotations\Annotation\Attributes;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Annotation to define a controller as widget in the Contao backend dashboard.
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * @Attributes({
 *     @Attribute("value", type = "string"),
 *     @Attribute("renderer", type = "string"),
 *     @Attribute("attributes", type = "array"),
 * })
 */
final class DashboardWidget extends AbstractFragmentAnnotation
{
    public function getName(): string
    {
        return DashboardWidgetReference::TAG_NAME;
    }
}
