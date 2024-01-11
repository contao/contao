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

class CspSaveCallbackListenerTest extends TestCase
{
    public function testValidatesTheCsp(): void
    {
        $cspParser = new CspParser(new PolicyManager());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown CSP directive name: foobar');

        (new CspSaveCallbackListener($cspParser))("foobar 'self'");
    }
}
