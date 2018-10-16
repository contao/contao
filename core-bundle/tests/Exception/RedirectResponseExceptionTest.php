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

use Contao\CoreBundle\Exception\RedirectResponseException;
use PHPUnit\Framework\TestCase;

class RedirectResponseExceptionTest extends TestCase
{
    public function testSetsTheResponseStatusCodeAndLocation(): void
    {
        $exception = new RedirectResponseException('http://example.org');
        $response = $exception->getResponse();

        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('http://example.org', $response->headers->get('Location'));
    }
}
