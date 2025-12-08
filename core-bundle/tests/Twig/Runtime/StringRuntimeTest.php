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

use Contao\CoreBundle\String\HtmlDecoder;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Runtime\StringRuntime;
use Contao\StringUtil;

class StringRuntimeTest extends TestCase
{
    public function testDelegatesCalls(): void
    {
        $stringUtil = $this->createAdapterStub(['encodeEmail']);
        $stringUtil
            ->expects($this->once())
            ->method('encodeEmail')
            ->with('email')
            ->willReturn('encoded email')
        ;

        $framework = $this->createContaoFrameworkStub([StringUtil::class => $stringUtil]);

        $htmlDecoder = $this->createMock(HtmlDecoder::class);
        $htmlDecoder
            ->expects($this->once())
            ->method('htmlToPlainText')
            ->with('html', true)
            ->willReturn('plain text')
        ;

        $htmlDecoder
            ->expects($this->once())
            ->method('inputEncodedToPlainText')
            ->with('input encoded html', true)
            ->willReturn('plain text')
        ;

        $stringRuntime = new StringRuntime($framework, $htmlDecoder);

        $this->assertSame('encoded email', $stringRuntime->encodeEmail('email'));
        $this->assertSame('plain text', $stringRuntime->htmlToPlainText('html', true));
        $this->assertSame('plain text', $stringRuntime->inputEncodedToPlainText('input encoded html', true));
    }
}
