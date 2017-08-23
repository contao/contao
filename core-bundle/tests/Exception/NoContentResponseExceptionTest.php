<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Exception;

use Contao\CoreBundle\Exception\NoContentResponseException;
use PHPUnit\Framework\TestCase;

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
    public function testCanBeInstantiated()
    {
        $exception = new NoContentResponseException();

        $this->assertInstanceOf('Contao\CoreBundle\Exception\NoContentResponseException', $exception);
    }

    /**
     * Tests the getResponse() method.
     */
    public function testSetsTheResponseStatusCode()
    {
        $exception = new NoContentResponseException();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $exception->getResponse());
        $this->assertSame(204, $exception->getResponse()->getStatusCode());
        $this->assertSame('', $exception->getResponse()->getContent());
    }
}
