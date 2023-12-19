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

use Contao\CoreBundle\Csp\CspParser;
use Contao\CoreBundle\EventListener\DataContainer\CspSaveCallbackListener;
use Contao\CoreBundle\Tests\TestCase;
use Nelmio\SecurityBundle\ContentSecurityPolicy\PolicyManager;
use Symfony\Contracts\Translation\TranslatorInterface;

class CspSaveCallbackListenerTest extends TestCase
{
    public function testValidatesTheCsp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('trans')
            ->with('ERR.invalidCsp', ['Unknown CSP directive name: foobar'], 'contao_default')
            ->willReturn('Invalid Content Security Policy given: Unknown CSP directive name: foobar.')
        ;

        $cspParser = new CspParser(new PolicyManager());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid Content Security Policy given: Unknown CSP directive name: foobar.');

        (new CspSaveCallbackListener($cspParser, $translator))("foobar 'self'");
    }
}
