<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\ResponseContext\Factory\Provider;

use Contao\CoreBundle\Routing\ResponseContext\Factory\Provider\CoreProvider;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\WebpageResponseContext;
use PHPUnit\Framework\TestCase;

class CoreProviderTest extends TestCase
{
    public function testSupports(): void
    {
        $provider = new CoreProvider();

        $this->assertTrue($provider->supports(ResponseContext::class));
        $this->assertTrue($provider->supports(WebpageResponseContext::class));
        $this->assertFalse($provider->supports('Foobar'));
    }

    public function testCreate(): void
    {
        $provider = new CoreProvider();

        $this->assertInstanceOf(ResponseContext::class, $provider->create(ResponseContext::class));
        $this->assertInstanceOf(WebpageResponseContext::class, $provider->create(WebpageResponseContext::class));
    }
}
