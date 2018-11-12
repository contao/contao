<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Exception;

use Contao\CoreBundle\Exception\NoContentResponseException;
use PHPUnit\Framework\TestCase;

class NoContentResponseExceptionTest extends TestCase
{
    public function testSetsTheResponseStatusCode(): void
    {
        $exception = new NoContentResponseException();

        $this->assertSame(204, $exception->getResponse()->getStatusCode());
        $this->assertSame('', $exception->getResponse()->getContent());
    }
}
