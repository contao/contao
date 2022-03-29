<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\Idna;
use PHPUnit\Framework\TestCase;

class IdnaTest extends TestCase
{
    public function testEncodesUnicodeDomain(): void
    {
        $this->assertSame('xn--fbar-5qaa.de', Idna::encode('fööbar.de'));
        $this->assertSame('', Idna::encode(''));
        $this->assertSame('', Idna::encode(sprintf('f%sbär.de', str_repeat('o', 53))));
    }

    public function testDecodesPunycodeDomain(): void
    {
        $this->assertSame('fööbar.de', Idna::decode('xn--fbar-5qaa.de'));
        $this->assertSame('', Idna::decode(''));
        $this->assertSame('', Idna::decode(sprintf('xn--f%sbr-tve.de', str_repeat('o', 56))));
    }

    public function testEncodesEmailAddresses(): void
    {
        $this->assertSame('info@xn--fbar-5qaa.de', Idna::encodeEmail('info@fööbar.de'));
        $this->assertSame('', Idna::encodeEmail(''));
        $this->assertSame('root', Idna::encodeEmail('root'));
        $this->assertSame('', Idna::encodeEmail(sprintf('info@f%sbär.de', str_repeat('o', 53))));
        $this->assertSame('Fööbar <info@xn--fbar-5qaa.de>', Idna::encodeEmail('Fööbar <info@fööbar.de>'));
    }

    public function testDecodesEmailAddresses(): void
    {
        $this->assertSame('info@fööbar.de', Idna::decodeEmail('info@xn--fbar-5qaa.de'));
        $this->assertSame('', Idna::decodeEmail(''));
        $this->assertSame('root', Idna::decodeEmail('root'));
        $this->assertSame('', Idna::decodeEmail(sprintf('info@xn--f%sbr-tve.de', str_repeat('o', 56))));
        $this->assertSame('Fööbar <info@fööbar.de>', Idna::decodeEmail('Fööbar <info@xn--fbar-5qaa.de>'));
    }

    public function testEncodesUrls(): void
    {
        $this->assertSame('https://www.xn--fbar-5qaa.de', Idna::encodeUrl('https://www.fööbar.de'));
        $this->assertSame('', Idna::encodeUrl(''));
        $this->assertSame('#', Idna::encodeUrl('#'));
        $this->assertSame('mailto:info@xn--fbar-5qaa.de', Idna::encodeUrl('mailto:info@fööbar.de'));

        $this->expectException('InvalidArgumentException');

        Idna::encodeUrl('www.fööbar.de');
        Idna::encodeUrl('index.php?foo=bar');
    }

    public function testDecodesUrls(): void
    {
        $this->assertSame('https://www.fööbar.de', Idna::decodeUrl('https://www.xn--fbar-5qaa.de'));
        $this->assertSame('', Idna::decodeUrl(''));
        $this->assertSame('#', Idna::decodeUrl('#'));
        $this->assertSame('mailto:info@fööbar.de', Idna::decodeUrl('mailto:info@xn--fbar-5qaa.de'));

        $this->expectException('InvalidArgumentException');

        Idna::decodeUrl('www.xn--fbar-5qaa.de');
        Idna::decodeUrl('index.php?foo=bar');
    }

    public function testHandlesQueryStrings(): void
    {
        $decoded = 'mailto:info@fööbar.de';
        $encoded = 'mailto:info@xn--fbar-5qaa.de';

        $queryString = '?subject='.str_repeat('a', 64);

        $this->assertSame($encoded.$queryString, Idna::encodeUrl($decoded.$queryString));
        $this->assertSame($decoded.$queryString, Idna::decodeUrl($encoded.$queryString));
    }
}
