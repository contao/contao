<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Exception;

use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Test\TestCase;

class RedirectResponseExceptionTest extends TestCase
{
    /**
     * Test the creation via static helper.
     *
     * @return void
     */
    public function testCreate()
    {
        $exception = RedirectResponseException::create('http://example.org');

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $exception->getResponse());
        $this->assertEquals(303, $exception->getResponse()->getStatusCode());
        $this->assertEquals('http://example.org', $exception->getResponse()->headers->get('Location'));
    }
}
