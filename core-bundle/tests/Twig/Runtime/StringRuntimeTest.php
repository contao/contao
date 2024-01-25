<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Runtime;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Runtime\StringRuntime;
use Contao\StringUtil;

class StringRuntimeTest extends TestCase
{
    public function testDelegatesCalls(): void
    {
        $stringUtil = $this->mockAdapter(['encodeEmail']);
        $stringUtil
            ->expects($this->once())
            ->method('encodeEmail')
            ->willReturn('&#102;&#x6F;&#111;&#x40;&#98;&#x61;&#114;&#x2E;&#99;&#x6F;&#109;')
        ;

        $framework = $this->mockContaoFramework([StringUtil::class => $stringUtil]);

        (new StringRuntime($framework))->encodeEmail('foo@bar.com');
    }
}
