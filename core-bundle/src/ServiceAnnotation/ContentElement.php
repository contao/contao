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
 * Annotation to define a controller as content element.
 *
 * @Annotation
 *
 * @Target({"CLASS", "METHOD"})
 *
 * @Attributes({
 *     @Attribute("value", type = "string"),
 *     @Attribute("category", required = true, type = "string"),
 *     @Attribute("template", type = "string"),
 *     @Attribute("renderer", type = "string"),
 *     @Attribute("attributes", type = "array"),
 * })
 *
 * @deprecated Deprecated since Contao 5.4, to be removed in Contao 6;
 *             use the #[AsContentElement] attribute instead
 */
final class ContentElement extends AbstractFragmentAnnotation
{
    public function getName(): string
    {
        trigger_deprecation('contao/core-bundle', '5.4', 'Using the @ContentElement annotation has been deprecated and will no longer work in Contao 6. Use the #[AsContentElement] attribute instead.');

        return ContentElementReference::TAG_NAME;
    }
}
