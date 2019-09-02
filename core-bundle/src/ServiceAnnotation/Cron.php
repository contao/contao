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
 * Annotation to register a Contao cron.
 *
 * @Annotation
 * @Target({"METHOD"})
 * @Attributes({
 *     @Attribute("value", type="string", required=true),
 *     @Attribute("priority", type="int"),
 *     @Attribute("cli", type="bool"),
 * })
 */
final class Cron implements ServiceTagInterface
{
    /**
     * @var string
     */
    public $value;

    /**
     * @var integer
     */
    public $priority;

    /**
     * @var boolean
     */
    public $cli;

    public function getName(): string
    {
        return 'contao.cron';
    }

    public function getAttributes(): array
    {
        $attributes = ['interval' => $this->value];

        if ($this->priority) {
            $attributes['priority'] = $this->priority;
        }

        if ($this->cli) {
            $attributes['cli'] = $this->cli;
        }

        return $attributes;
    }
}
