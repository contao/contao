<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Search\Backend\IndexUpdateConfig;

/**
 * @experimental
 */
abstract class AbstractIndexUpdateConfig implements IndexUpdateConfigInterface
{
    public function __construct(private readonly \DateTimeInterface|null $updateSince = null)
    {
    }

    public function getUpdateSince(): \DateTimeInterface|null
    {
        return $this->updateSince;
    }
}
