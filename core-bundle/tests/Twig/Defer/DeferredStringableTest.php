<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Defer;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Defer\DeferredStringable;

class DeferredStringableTest extends TestCase
{
    public function testEvaluatesClosureWhenOutput(): void
    {
        $evaluated = false;

        $deferredStringable = new DeferredStringable(
            static function () use (&$evaluated) {
                $evaluated = true;

                return 'foo';
            },
        );

        $this->assertFalse($evaluated);
        $this->assertSame('foo', (string) $deferredStringable);
        $this->assertTrue($evaluated);
    }
}
