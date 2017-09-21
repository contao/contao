<?php

declare(strict_types=1);

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

class NoContentResponseExceptionTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $exception = new NoContentResponseException();

        $this->assertInstanceOf('Contao\CoreBundle\Exception\NoContentResponseException', $exception);
    }

    public function testSetsTheResponseStatusCode(): void
    {
        $exception = new NoContentResponseException();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $exception->getResponse());
        $this->assertSame(204, $exception->getResponse()->getStatusCode());
        $this->assertSame('', $exception->getResponse()->getContent());
    }
}
