<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\CallableProxy;
use stdClass;

class CallableProxyTest extends TestCase
{
    public function testDelegatesCallsWhenInvoked(): void
    {
        $context = new stdClass();
        $context->text = 'eager';

        $lazyText = static function () use ($context): string {
            return "I am {$context->text}.";
        };

        $proxy = new CallableProxy($lazyText);

        $this->assertSame($lazyText, $proxy->getInner());

        $context->text = 'lazy';
        $this->assertSame('I am lazy.', (string) $proxy);
        $this->assertSame('I am lazy.', $proxy->invoke());
    }

    public function testDelegatesCallsToArbitraryFunction(): void
    {
        $addFunction = static function (int $a, int $b): string {
            return sprintf('%d + %d = %d', $a, $b, $a + $b);
        };

        $proxy = new CallableProxy($addFunction);

        $this->assertSame('1 + 2 = 3', $proxy->invoke(1, 2));
    }
}
