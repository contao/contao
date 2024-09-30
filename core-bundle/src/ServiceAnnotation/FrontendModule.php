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
 *
 * @deprecated Deprecated since Contao 5.4, to be removed in Contao 6;
 *             use the #[AsFrontendModule] attribute instead
 */
final class FrontendModule extends AbstractFragmentAnnotation
{
    public function getName(): string
    {
        trigger_deprecation('contao/core-bundle', '5.4', 'Using the @FrontendModule annotation has been deprecated and will no longer work in Contao 6. Use the #[AsFrontendModule] attribute instead.');

        return FrontendModuleReference::TAG_NAME;
    }
}
