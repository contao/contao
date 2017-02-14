<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Exception;

use Contao\CoreBundle\Exception\ResponseException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the ResponseException class.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class ResponseExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $exception = new ResponseException(new Response('Hello world'));

        $this->assertInstanceOf('Contao\CoreBundle\Exception\ResponseException', $exception);
    }

    /**
     * Tests the getResponse() method.
     */
    public function testGetResponse()
    {
        $exception = new ResponseException(new Response('Hello world'));

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $exception->getResponse());
        $this->assertEquals(200, $exception->getResponse()->getStatusCode());
        $this->assertEquals('Hello world', $exception->getResponse()->getContent());
    }
}
