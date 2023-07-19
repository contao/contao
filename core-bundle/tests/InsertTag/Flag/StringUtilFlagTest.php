<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\InsertTag\Flag;

use Contao\CoreBundle\InsertTag\Flag\StringUtilFlag;
use Contao\CoreBundle\InsertTag\InsertTagFlag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\Tests\TestCase;

class StringUtilFlagTest extends TestCase
{
    public function testUtf8Ucfirst(): void
    {
        $this->assertSame(
            'Österreich',
            (new StringUtilFlag())->utf8Ucfirst(new InsertTagFlag('utf8_ucfirst'), new InsertTagResult('österreich'))->getValue(),
        );
    }

    public function testUtf8Lcfirst(): void
    {
        $this->assertSame(
            'österreich',
            (new StringUtilFlag())->utf8Lcfirst(new InsertTagFlag('utf8_lcfirst'), new InsertTagResult('Österreich'))->getValue(),
        );
    }

    public function testUtf8Ucwords(): void
    {
        $this->assertSame(
            "Österreich Und Ägypten\nHaben Ca. 100 Einwohner\tPro Km²",
            (new StringUtilFlag())->utf8Ucwords(new InsertTagFlag('utf8_ucwords'), new InsertTagResult("österreich und ägypten\nhaben ca. 100 einwohner\tpro km²"))->getValue(),
        );
    }
}
