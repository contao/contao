<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Filesystem;

use Contao\CoreBundle\Filesystem\UnableToSetExtraMetadataException;
use Contao\CoreBundle\Tests\TestCase;

class UnableToSetExtraMetadataExceptionTest extends TestCase
{
    public function testGetValues(): void
    {
        $previous = new \InvalidArgumentException('something is wrong');
        $exception = new UnableToSetExtraMetadataException('foo/bar', $previous);

        $this->assertSame($previous, $exception->getPrevious());

        $this->assertSame(
            'Unable to set extra metadata for location \'foo/bar\'.',
            $exception->getMessage()
        );
    }
}
