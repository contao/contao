<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Event;

use Contao\CoreBundle\Event\JsonLdEvent;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use PHPUnit\Framework\TestCase;

class JsonEventTest extends TestCase
{
    public function testCanSetAndGetResponseContext(): void
    {
        $context = new ResponseContext();

        $event = new JsonLdEvent();
        $event->setResponseContext($context);

        $this->assertSame($context, $event->getResponseContext());
    }

    public function testThrowsExceptionWhenTryingToSetTheContextTwice(): void
    {
        $context = new ResponseContext();

        $event = new JsonLdEvent();
        $event->setResponseContext($context);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('ResponseContext is already set!');

        $event->setResponseContext($context);
    }

    public function testThrowsExceptionWhenTryingToAccessEmptyContext(): void
    {
        $event = new JsonLdEvent();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('ResponseContext must be set!');

        $event->getResponseContext();
    }
}
