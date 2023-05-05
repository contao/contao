<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Dotenv;

use Contao\ManagerBundle\Dotenv\DotenvDumper;
use Contao\TestCase\ContaoTestCase;

class DotenvDumperTest extends ContaoTestCase
{
    public function testDumpsADotenvFile(): void
    {
        $dotenv = new DotenvDumper($this->getTempDir().'/.env');
        $dotenv->setParameter('FOO1', 42);
        $dotenv->setParameter('FOO2', '42');
        $dotenv->setParameter('FOO3', 'String');
        $dotenv->setParameter('FOO4', 'String with spaces');
        $dotenv->setParameter('FOO5', '"DoubleQuotes"');
        $dotenv->setParameter('FOO6', "'SingleQuotes'");
        $dotenv->setParameter('FOO7', '$variable');
        $dotenv->setParameter('FOO8', 'String with "double quotes" and \'single quotes\' and a $variable');
        $dotenv->dump();

        $expected = <<<'EOT'
            FOO1=42
            FOO2=42
            FOO3=String
            FOO4='String with spaces'
            FOO5='"DoubleQuotes"'
            FOO6="'SingleQuotes'"
            FOO7='$variable'
            FOO8="String with \"double quotes\" and 'single quotes' and a \$variable"

            EOT;

        $this->assertSame($expected, file_get_contents($this->getTempDir().'/.env'));
    }

    public function testSupportsUnsettingParameters(): void
    {
        $dotenv = new DotenvDumper($this->getTempDir().'/.env.local');
        $dotenv->setParameter('FOO', 'bar');
        $dotenv->setParameter('BAR', 'foo');
        $dotenv->unsetParameter('FOO');
        $dotenv->dump();

        $this->assertSame("BAR=foo\n", file_get_contents($this->getTempDir().'/.env.local'));
    }

    public function testKeepsParametersUntouched(): void
    {
        $original = <<<'EOT'
            FOO='bar' # comment
            BAR="foo"

            # comment after empty line
            BAZ=${FOO}${BAR}

            EOT;

        file_put_contents($this->getTempDir().'/.env.local', $original);

        $dotenv = new DotenvDumper($this->getTempDir().'/.env.local');
        $dotenv->setParameter('BAZ', 'barfoo');
        $dotenv->setParameter('NEW', 'value');
        $dotenv->dump();

        $this->assertSame($original."NEW=value\n", file_get_contents($this->getTempDir().'/.env.local'));
    }
}
