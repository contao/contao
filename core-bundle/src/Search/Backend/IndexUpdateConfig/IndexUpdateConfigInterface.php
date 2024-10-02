<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Search\Backend\IndexUpdateConfig;

/**
 * @experimental
 */
interface IndexUpdateConfigInterface
{
    public function getUpdateSince(): \DateTimeInterface|null;
}
