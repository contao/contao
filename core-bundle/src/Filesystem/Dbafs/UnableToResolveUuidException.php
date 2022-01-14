<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem\Dbafs;

use Symfony\Component\Uid\Uuid;

/**
 * @experimental
 */
class UnableToResolveUuidException extends \RuntimeException
{
    private Uuid $uuid;

    public function __construct(Uuid $uuid, string $message = '')
    {
        $this->uuid = $uuid;

        parent::__construct(rtrim(sprintf('Unable to resolve UUID "%s" to a path. %s', $uuid->toRfc4122(), $message)));
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }
}
