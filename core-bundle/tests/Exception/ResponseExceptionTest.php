<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Exception;

use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Test\TestCase;

/**
 * Tests the ResponseException class.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class ResponseExceptionTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $exception = new ResponseException('Hello world');

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $exception->getResponse());
        $this->assertEquals(200, $exception->getResponse()->getStatusCode());
        $this->assertEquals('Hello world', $exception->getResponse()->getContent());
    }
}
