<?php

declare(strict_types=1);

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

class AjaxRedirectResponseExceptionTest extends TestCase
{
    public function testSetsTheResponseStatusCodeAndAjaxLocation(): void
    {
        $exception = new AjaxRedirectResponseException('http://example.org');
        $response = $exception->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('http://example.org', $response->headers->get('X-Ajax-Location'));
    }
}
