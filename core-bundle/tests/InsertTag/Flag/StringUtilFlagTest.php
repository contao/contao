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
    /**
     * @dataProvider getFlags
     */
    public function testFlags(string $flagName, string $source, string $expected): void
    {
        $flag = new StringUtilFlag();

        $this->assertSame($expected, $flag->$flagName(new InsertTagFlag($flagName), new InsertTagResult($source))->getValue());
    }

    public function getFlags(): \Generator
    {
        yield ['strtolower', 'FOO', 'foo'];
        yield ['strtoupper', 'foo', 'FOO'];
        yield ['ucfirst', 'foo', 'Foo'];
        yield ['lcfirst', 'FOO', 'fOO'];
        yield ['ucwords', 'foo bar', 'Foo Bar'];
        yield ['strtolower', 'FÖO', 'föo'];
        yield ['strtoupper', 'föo', 'FÖO'];
        yield ['ucfirst', 'öof', 'Öof'];
        yield ['lcfirst', 'ÖOF', 'öOF'];
        yield ['ucwords', 'öof bar', 'Öof Bar'];
    }
}
