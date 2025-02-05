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
 *
 * @Target({"CLASS", "METHOD"})
 *
 * @Attributes({
 *     @Attribute("value", type="string", required=true)
 * })
 *
 * @deprecated Deprecated since Contao 5.4, to be removed in Contao 6;
 *             use the #[AsCronJob] attribute instead
 */
final class CronJob implements ServiceTagInterface
{
    public string|null $value = null;

    public function getName(): string
    {
        trigger_deprecation('contao/core-bundle', '5.4', 'Using the @CronJob annotation has been deprecated and will no longer work in Contao 6. Use the #[AsCronJob] attribute instead.');

        return 'contao.cronjob';
    }

    public function getAttributes(): array
    {
        // Replace escaped characters
        $this->value = preg_replace('#\\\\([\\\\/"])#', '$1', $this->value);

        return ['interval' => $this->value];
    }
}
