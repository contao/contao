<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\Idna;
use PHPUnit\Framework\TestCase;
use TrueBV\Exception\DomainOutOfBoundsException;

/**
 * Tests the Idna class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 *
 * @group contao3
 */
class IdnaTest extends TestCase
{
    /**
     * Tests the encode() method.
     */
    public function testEncode()
    {
        $this->assertSame('xn--fbar-5qaa.de', Idna::encode('fööbar.de'));
        $this->assertSame('', Idna::encode(''));
        $this->assertSame('', Idna::encode(sprintf('f%sbär.de', str_repeat('o', 53))));

        $this->expectException(DomainOutOfBoundsException::class);

        Idna::encode(str_repeat('subdomain.', 24).'fööbar.de');
    }

    /**
     * Tests the decode() method.
     */
    public function testDecode()
    {
        $this->assertSame('fööbar.de', Idna::decode('xn--fbar-5qaa.de'));
        $this->assertSame('', Idna::decode(''));
        $this->assertSame('', Idna::decode(sprintf('xn--f%sbr-tve.de', str_repeat('o', 53))));

        $this->expectException(DomainOutOfBoundsException::class);

        Idna::decode(str_repeat('subdomain.', 25).'xn--fbar-5qaa.de');
    }

    /**
     * Tests the encodeEmail() method.
     */
    public function testEncodeEmail()
    {
        $this->assertSame('info@xn--fbar-5qaa.de', Idna::encodeEmail('info@fööbar.de'));
        $this->assertSame('', Idna::encodeEmail(''));
        $this->assertSame('root', Idna::encodeEmail('root'));
        $this->assertSame('', Idna::encodeEmail(sprintf('info@f%sbär.de', str_repeat('o', 53))));
        $this->assertSame('Fööbar <info@xn--fbar-5qaa.de>', Idna::encodeEmail('Fööbar <info@fööbar.de>'));

        $this->expectException(DomainOutOfBoundsException::class);

        Idna::encodeEmail('info@'.str_repeat('subdomain.', 24).'fööbar.de');
    }

    /**
     * Tests the decodeEmail() method.
     */
    public function testDecodeEmail()
    {
        $this->assertSame('info@fööbar.de', Idna::decodeEmail('info@xn--fbar-5qaa.de'));
        $this->assertSame('', Idna::decodeEmail(''));
        $this->assertSame('root', Idna::decodeEmail('root'));
        $this->assertSame('', Idna::decodeEmail(sprintf('info@xn--f%sbr-tve.de', str_repeat('o', 53))));
        $this->assertSame('Fööbar <info@fööbar.de>', Idna::decodeEmail('Fööbar <info@xn--fbar-5qaa.de>'));

        $this->expectException(DomainOutOfBoundsException::class);

        Idna::decodeEmail('info@'.str_repeat('subdomain.', 25).'xn--f%sbr-tve.de');
    }

    /**
     * Tests the encodeUrl() method.
     */
    public function testEncodeUrl()
    {
        $this->assertSame('http://www.xn--fbar-5qaa.de', Idna::encodeUrl('http://www.fööbar.de'));
        $this->assertSame('', Idna::encodeUrl(''));
        $this->assertSame('#', Idna::encodeUrl('#'));
        $this->assertSame('mailto:info@xn--fbar-5qaa.de', Idna::encodeUrl('mailto:info@fööbar.de'));

        $this->expectException('InvalidArgumentException');

        Idna::encodeUrl('www.fööbar.de');
        Idna::encodeUrl('index.php?foo=bar');
    }

    /**
     * Tests the decodeUrl() method.
     */
    public function testDecodeUrl()
    {
        $this->assertSame('http://www.fööbar.de', Idna::decodeUrl('http://www.xn--fbar-5qaa.de'));
        $this->assertSame('', Idna::decodeUrl(''));
        $this->assertSame('#', Idna::decodeUrl('#'));
        $this->assertSame('mailto:info@fööbar.de', Idna::decodeUrl('mailto:info@xn--fbar-5qaa.de'));

        $this->expectException('InvalidArgumentException');

        Idna::decodeUrl('www.xn--fbar-5qaa.de');
        Idna::decodeUrl('index.php?foo=bar');
    }
}
