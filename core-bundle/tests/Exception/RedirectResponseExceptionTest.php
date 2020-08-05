<?php

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

/**
 * Tests the RedirectResponseException class.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class RedirectResponseExceptionTest extends TestCase
{
    /**
     * Tests the getResponse() method.
     */
    public function testSetsTheResponseStatusCodeAndLocation()
    {
        $exception = new RedirectResponseException('http://example.org');

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $exception->getResponse());
        $this->assertSame(303, $exception->getResponse()->getStatusCode());
        $this->assertSame('http://example.org', $exception->getResponse()->headers->get('Location'));
    }
}
