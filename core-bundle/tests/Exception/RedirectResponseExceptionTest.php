<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Exception;

use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Test\TestCase;

/**
 * Tests the RedirectResponseException class.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class RedirectResponseExceptionTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $exception = new RedirectResponseException('http://example.org');

        $this->assertInstanceOf('Contao\CoreBundle\Exception\RedirectResponseException', $exception);
    }

    /**
     * Tests the getResponse() method.
     */
    public function testGetResponse()
    {
        $exception = new RedirectResponseException('http://example.org');

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $exception->getResponse());
        $this->assertEquals(303, $exception->getResponse()->getStatusCode());
        $this->assertEquals('http://example.org', $exception->getResponse()->headers->get('Location'));
    }
}
