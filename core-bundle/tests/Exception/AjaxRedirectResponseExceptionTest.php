<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Exception;

use Contao\CoreBundle\Exception\AjaxRedirectResponseException;
use PHPUnit\Framework\TestCase;

/**
 * Tests the AjaxRedirectResponseException class.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class AjaxRedirectResponseExceptionTest extends TestCase
{
    /**
     * Tests the getResponse() method.
     */
    public function testSetsTheResponseStatusCodeAndAjaxLocation()
    {
        $exception = new AjaxRedirectResponseException('http://example.org');

        $response = $exception->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('http://example.org', $response->headers->get('X-Ajax-Location'));
    }
}
