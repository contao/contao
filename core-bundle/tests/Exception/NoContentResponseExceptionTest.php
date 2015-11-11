<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Exception;

use Contao\CoreBundle\Exception\NoContentResponseException;
use Contao\CoreBundle\Test\TestCase;

/**
 * Tests the NoContentResponseException class.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class NoContentResponseExceptionTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $exception = new NoContentResponseException();

        $this->assertInstanceOf('Contao\CoreBundle\Exception\NoContentResponseException', $exception);
    }

    /**
     * Tests the getResponse() method.
     */
    public function testGetResponse()
    {
        $exception = new NoContentResponseException();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $exception->getResponse());
        $this->assertEquals(204, $exception->getResponse()->getStatusCode());
        $this->assertEquals('', $exception->getResponse()->getContent());
    }
}
