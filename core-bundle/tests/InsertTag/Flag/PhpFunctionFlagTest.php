<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\InsertTag;

use Contao\CoreBundle\InsertTag\Flag\PhpFunctionFlag;
use Contao\CoreBundle\InsertTag\InsertTagFlag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\Tests\TestCase;

class PhpFunctionFlagTest extends TestCase
{
    /**
     * @dataProvider getFlags
     */
    public function testFlags(string $flagName, string $source, string $expected): void
    {
        $flag = new PhpFunctionFlag();
        $this->assertSame($expected, $flag(new InsertTagFlag($flagName), new InsertTagResult($source))->getValue());
    }

    public function getFlags(): \Generator
    {
        yield ['addslashes', "foo'bar", "foo\\'bar"];
        yield ['strtolower', 'FOO', 'foo'];
        yield ['strtoupper', 'foo', 'FOO'];
        yield ['ucfirst', 'foo', 'Foo'];
        yield ['lcfirst', 'FOO', 'fOO'];
        yield ['ucwords', 'foo bar', 'Foo Bar'];
        yield ['trim', "\t\n foo\t\n ", 'foo'];
        yield ['rtrim', "\t\n foo\t\n ", "\t\n foo"];
        yield ['ltrim', "\t\n foo\t\n ", "foo\t\n "];
        yield ['urlencode', 'foö bar', 'fo%C3%B6+bar'];
        yield ['rawurlencode', 'foö bar', 'fo%C3%B6%20bar'];
    }

    public function testDoesNotExecuteArbitraryFunctions(): void
    {
        $flag = new PhpFunctionFlag();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Invalid flag "eval".');

        $flag(new InsertTagFlag('eval'), new InsertTagResult('throw new RuntimeException("This should not be executed.");'));
    }
}
