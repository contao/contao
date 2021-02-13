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

use Contao\CoreBundle\Fragment\Reference\BackendModuleReference;
use Doctrine\Common\Annotations\Annotation\Attribute;
use Doctrine\Common\Annotations\Annotation\Attributes;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Annotation to define a controller as backend module.
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * @Attributes({
 *     @Attribute("value", type="string"),
 *     @Attribute("category", type="string", required = true),
 *     @Attribute("renderer", type="string"),
 *     @Attribute("disablePermissionChecks", type="bool"),
 *     @Attribute("hideInNavigation", type="bool")
 * })
 */
final class BackendModule extends AbstractFragmentAnnotation
{
    public function getName(): string
    {
        return BackendModuleReference::TAG_NAME;
    }
}
