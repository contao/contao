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

use Contao\CoreBundle\Exception\RedirectResponseException;
use PHPUnit\Framework\TestCase;

class RedirectResponseExceptionTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $exception = new RedirectResponseException('http://example.org');

        $this->assertInstanceOf('Contao\CoreBundle\Exception\RedirectResponseException', $exception);
    }

    public function testSetsTheResponseStatusCodeAndLocation(): void
    {
        $exception = new RedirectResponseException('http://example.org');

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $exception->getResponse());
        $this->assertSame(303, $exception->getResponse()->getStatusCode());
        $this->assertSame('http://example.org', $exception->getResponse()->headers->get('Location'));
    }
}
