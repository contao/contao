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

use Doctrine\Common\Annotations\Annotation\Attribute;
use Doctrine\Common\Annotations\Annotation\Attributes;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Annotation that can be used to define controller as a frontend module.
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * @Attributes({
 *     @Attribute("category", required = true, type = "string"),
 *     @Attribute("options", type = "array"),
 *     @Attribute("renderer", type = "string"),
 *     @Attribute("service", type = "string"),
 *     @Attribute("template", type = "string"),
 *     @Attribute("type", type = "string"),
 * })
 */
final class FrontendModule extends Base
{
}
