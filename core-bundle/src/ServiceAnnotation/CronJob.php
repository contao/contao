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
 * Annotation to register a Contao cron job.
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * @Attributes({
 *     @Attribute("value", type="string", required=true)
 * })
 */
final class CronJob implements ServiceTagInterface
{
    public string|null $value = null;

    public function getName(): string
    {
        return 'contao.cronjob';
    }

    public function getAttributes(): array
    {
        // Replace escaped characters
        $this->value = preg_replace('#\\\\([\\\\/"])#', '$1', $this->value);

        return ['interval' => $this->value];
    }
}
