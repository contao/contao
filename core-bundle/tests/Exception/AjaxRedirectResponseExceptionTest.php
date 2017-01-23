<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Exception;

use Contao\CoreBundle\Exception\AjaxRedirectResponseException;

/**
 * Tests the AjaxRedirectResponseException class.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class AjaxRedirectResponseExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $exception = new AjaxRedirectResponseException('http://example.org');

        $this->assertInstanceOf('Contao\CoreBundle\Exception\AjaxRedirectResponseException', $exception);
    }

    /**
     * Tests the getResponse() method.
     */
    public function testGetResponse()
    {
        $exception = new AjaxRedirectResponseException('http://example.org');

        $response = $exception->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('http://example.org', $response->headers->get('X-Ajax-Location'));
    }
}
