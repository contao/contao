<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\EventListener\DataContainer\CspSaveCallbackListener;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class CspSaveCallbackListenerTest extends TestCase
{
    public function testValidatesTheCsp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('trans')
            ->with('ERR.invalidCsp', ['Directive foobar does not exist'], 'contao_default')
            ->willReturn('Invalid Content Security Policy given: Directive "foobar" does not exist.')
        ;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid Content Security Policy given: Directive "foobar" does not exist.');

        (new CspSaveCallbackListener($translator))("foobar 'self'");
    }
}
