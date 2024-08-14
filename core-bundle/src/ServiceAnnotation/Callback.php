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

use Doctrine\Common\Annotations\Annotation\Attribute;
use Doctrine\Common\Annotations\Annotation\Attributes;
use Doctrine\Common\Annotations\Annotation\Target;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTagInterface;

/**
 * Annotation to register a DCA callback.
 *
 * @Annotation
 *
 * @Target({"CLASS", "METHOD"})
 *
 * @Attributes({
 *     @Attribute("table", type="string", required=true),
 *     @Attribute("target", type="string", required=true),
 *     @Attribute("priority", type="int"),
 * })
 *
 * @deprecated Deprecated since Contao 5.4, to be removed in Contao 6;
 *             use the #[AsCallback] attribute instead
 */
final class Callback implements ServiceTagInterface
{
    public string $table;

    public string $target;

    public int|null $priority = null;

    public function getName(): string
    {
        trigger_deprecation('contao/core-bundle', '5.4', 'Using the @Callback annotation has been deprecated and will no longer work in Contao 6. Use the #[AsCallback] attribute instead.');

        return 'contao.callback';
    }

    public function getAttributes(): array
    {
        $attributes = [
            'table' => $this->table,
            'target' => $this->target,
        ];

        if (null !== $this->priority) {
            $attributes['priority'] = $this->priority;
        }

        return $attributes;
    }
}
