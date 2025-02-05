<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Filesystem\Dbafs;

use Contao\CoreBundle\Filesystem\Dbafs\UnableToResolveUuidException;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Uid\Uuid;

class UnableToResolveUuidExceptionTest extends TestCase
{
    public function testGetValues(): void
    {
        $uuid = Uuid::fromString('2bfce622-5ea5-11ec-bf63-0242ac130002');
        $exception = new UnableToResolveUuidException($uuid, 'Additional message.');

        $this->assertSame($uuid, $exception->getUuid());

        $this->assertSame(
            'Unable to resolve UUID "2bfce622-5ea5-11ec-bf63-0242ac130002" to a path. Additional message.',
            $exception->getMessage(),
        );
    }
}
