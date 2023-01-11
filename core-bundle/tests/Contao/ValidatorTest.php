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
use Contao\StringUtil;
use Contao\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    /**
     * @dataProvider emailProvider
     */
    public function testValidatesEmailAddresses(string $email, bool|int $expected): void
    {
        $this->assertSame(
            $expected,
            Validator::isEmail($email),
            'Original: '.$email.' idna: '.Idna::encodeEmail($email)
        );
    }

    public function emailProvider(): \Generator
    {
        // Valid ASCII
        yield ['niceandsimple@example.com', 1];
        yield ['very.common@example.com', 1];
        yield ['a.little.lengthy.but.fine@dept.example.com', 1];
        yield ['disposable.style.email.with+symbol@example.com', 1];
        yield ['other.email-with-dash@example.com', 1];
        yield ['"very.unusual.@.unusual.com"@example.com', 1];
        yield ['"very.(),:;<>[]\".VERY.\"very@\ \"very\".unusual"@strange.example.com', 1];
        yield ['!#$%&\'*+-/=?^_`{}|~@example.org', 1];
        yield ['"()<>[]:,;@\"!#$%&\'*+-/=?^_`{}|~.a"@example.org', 1];

        // Valid with IP addresses
        yield ['user@[255.255.255.255]', 1];
        yield ['user@[IPv6:2001:db8:1ff::a0b:dbd0]', 1];
        yield ['user@[IPv6:2001:0db8:85a3:08d3:1319:8a2e:0370:7344]', 1];
        yield ['user@[IPv6:2001::7344]', 1];
        yield ['user@[IPv6:1111:2222:3333:4444:5555:6666:255.255.255.255]', 1];

        // Valid with IDNA domains
        yield ['test@exämple.com', 1];
        yield ['test@ä.xe', 1];
        yield ['test@subexample.wizard', 1];
        yield ['test@wähwähwäh.ümläüts.de', 1];
        yield ['"tes@t"@wähwähwäh.ümläüts.de', 1];

        // Valid with new TLDs
        yield ['test@example.photography', 1];
        yield ['test@sub-domain.example.photography', 1];

        // Valid with unicode characters in the local part
        yield ['niceändsimple@example.com', 1];
        yield ['véry.çommon@example.com', 1];
        yield ['a.lîttle.lengthy.but.fiñe@dept.example.com', 1];
        yield ['dîsposable.style.émail.with+symbol@example.com', 1];
        yield ['other.émail-with-dash@example.com', 1];
        yield ['"verî.uñusual.@.uñusual.com"@example.com', 1];
        yield ['"verî.(),:;<>[]\".VERÎ.\"verî@\ \"verî\".unüsual"@strange.example.com', 1];
        yield ['üñîçøðé@example.com', 1];
        yield ['"üñîçøðé"@example.com', 1];
        yield ['ǅǼ੧ఘⅧ⒇৪@example.com', 1];

        // Valid with IP addresses and unicode characters in the local part
        yield ['üser@[255.255.255.255]', 1];
        yield ['üser@[IPv6:2001:db8:1ff::a0b:dbd0]', 1];
        yield ['üser@[IPv6:2001:0db8:85a3:08d3:1319:8a2e:0370:7344]', 1];
        yield ['üser@[IPv6:2001::7344]', 1];
        yield ['üser@[IPv6:1111:2222:3333:4444:5555:6666:255.255.255.255]', 1];

        // Valid with IDNA domains and unicode characters in the local part
        yield ['tést@exämple.com', 1];
        yield ['tést@ä.xe', 1];
        yield ['tést@subexample.wizard', 1];
        yield ['tést@wähwähwäh.ümläüts.de', 1];
        yield ['"tés@t"@wähwähwäh.ümläüts.de', 1];

        // Valid with new TLDs and unicode characters in the local part
        yield ['tést@example.photography', 1];
        yield ['tést@sub-domain.example.photography', 1];

        // Invalid ASCII
        yield ['test..child@example.com', 0];
        yield ['test@sub.-example.com', 0];
        yield ['test@_smtp_.example.com', 0];
        yield ['test@sub..example.com', 0];
        yield ['test@subexamplecom', 0];
        yield ['Abc.example.com', 0];
        yield ['A@b@c@example.com', 0];
        yield ['a"b(c)d,e:f;gi[j\k]l@example.com', 0];
        yield ['just"not"right@example.com', 0];
        yield ['this is"not\allowed@example.com', 0];
        yield ['this\ still\"not\allowed@example.com', 0];
        yield ['(comment)test@iana.org', 0];
        yield ['test@[1.2.3.4', 0];
        yield ['test@iana.org-', 0];
        yield ['', 0];
        yield ['test', 0];
        yield ['@', 0];
        yield ['test@', 0];

        // Invalid with IP addresses
        yield ['test@a[255.255.255.255]', 0];
        yield ['test@[255.255.255]', 0];
        yield ['test@[255.255.255.255.255]', 0];
        yield ['test@[255.255.255.256]', 0];
        yield ['test@[2001::7344]', 0];
        yield ['test@[IPv6:1111:2222:3333:4444:5555:6666:7777:255.255.255.255]', 0];

        // Invalid with IDNA domain
        yield ['tes@t@wähwähwäh.ümläüts.de', 0];
        yield [' test@wähwähwäh.ümläüts.de', 0];

        // Invalid with new TLDs
        yield ['tes@t@example.photography', 0];
        yield [' test@sub-domain.example.photography', 0];

        // Invalid with unicode characters in the local part
        yield ['tést..child@example.com', 0];
        yield ['tést@sub.-example.com', 0];
        yield ['tést@_smtp_.example.com', 0];
        yield ['tést@sub..example.com', 0];
        yield ['tést@subexamplecom', 0];
        yield ['Abç.example.com', 0];
        yield ['Â@ఘ@ç@example.com', 0];
        yield ['â"ఘ(ç)d,e:f;gi[j\k]l@example.com', 0];
        yield ['jüst"not"rîght@example.com', 0];
        yield ['this îs"not\alløwed@example.com', 0];
        yield ['this\ stîll\"not\alløwed@example.com', 0];
        yield ['(çommént)tést@iana.org', 0];
        yield ['tést@[1.2.3.4', 0];
        yield ['tést@iana.org-', 0];
        yield ['tést@', 0];

        // Invalid with IP addresses and unicode characters in the local part
        yield ['tést@a[255.255.255.255]', 0];
        yield ['tést@[255.255.255]', 0];
        yield ['tést@[255.255.255.255.255]', 0];
        yield ['tést@[255.255.255.256]', 0];
        yield ['tést@[2001::7344]', 0];
        yield ['tést@[IPv6:1111:2222:3333:4444:5555:6666:7777:255.255.255.255]', 0];

        // Invalid with IDNA domains and unicode characters in the local part
        yield ['tés@t@wähwähwäh.ümläüts.de', 0];
        yield [' tést@wähwähwäh.ümläüts.de', 0];

        // Invalid with new TLDs and unicode characters in the local part
        yield ['tés@t@example.photography', 0];
        yield [' tést@sub-domain.example.photography', 0];
    }

    public function testExtractsEmailAddressesFromText(): void
    {
        $text = <<<'EOF'
            This is a niceandsimple@example.com and this a very.common@example.com. Another little.lengthy.but.fine@dept.example.com and also a disposable.style.email.with+symbol@example.com or an other.email-with-dash@example.com. There are "very.unusual.@.unusual.com"@example.com and "very.(),:;<>[]\".VERY.\"very@\ \"very\".unusual"@strange.example.com and even !#$%&'*+-/=?^_`{}|~@example.org or "()<>[]:,;@\"!#$%&'*+-/=?^_`{}|~.a"@example.org but they are all valid.
            IP addresses as in user@[255.255.255.255], user@[IPv6:2001:db8:1ff::a0b:dbd0], user@[IPv6:2001:0db8:85a3:08d3:1319:8a2e:0370:7344], user@[IPv6:2001::7344] or user@[IPv6:1111:2222:3333:4444:5555:6666:255.255.255.255] are valid, too.
            We also support IDNA domains as in test@exämple.com, test@ä.xe, test@subexample.wizard, test@wähwähwäh.ümläüts.de or "tes@t"@wähwähwäh.ümläüts.de. And we support new TLDs as in test@example.photography or test@sub-domain.example.photography.
            And we support unicode characters in the local part (RFC 6531) as in niceändsimple@example.com, véry.çommon@example.com, a.lîttle.lengthy.but.fiñe@dept.example.com, dîsposable.style.émail.with+symbol@example.com, other.émail-with-dash@example.com, "verî.uñusual.@.uñusual.com"@example.com, "verî.(),:;<>[]\".VERÎ.\"verî@\ \"verî\".unüsual"@strange.example.com, üñîçøðé@example.com, "üñîçøðé"@example.com or ǅǼ੧ఘⅧ⒇৪@example.com.
            Of course also with IP addresses: üser@[255.255.255.255], üser@[IPv6:2001:db8:1ff::a0b:dbd0], üser@[IPv6:2001:0db8:85a3:08d3:1319:8a2e:0370:7344], üser@[IPv6:2001::7344] or üser@[IPv6:1111:2222:3333:4444:5555:6666:255.255.255.255] and unicode characters in the local part: tést@exämple.com, tést@ä.xe, tést@subexample.wizard, tést@wähwähwäh.ümläüts.de, "tés@t"@wähwähwäh.ümläüts.de. New TLDs? No problem: tést@example.photography or tést@sub-domain.example.photography.
            And hopefully we do not match invalid addresses such as test..child@example.com, test@sub.-example.com, test@_smtp_.example.com, test@sub..example.com, test@subexamplecom, Abc.example.com, a"b(c)d,e:f;gi[j\k]l@example.com, this is"not\allowed@example.com, this\ still\"not\allowed@example.com, (comment)test@iana.org, test@[1.2.3.4, @ or test@.
            Also, we should correctly parse <a href="mailto:tricky@example.com">tricky@example.com</a>, <a href="mailto:more-tricky@example.com">write us</a> and <strong>even-more-tricky@example.com</strong>.
            EOF;

        $expected = [
            'niceandsimple@example.com',
            'very.common@example.com',
            'little.lengthy.but.fine@dept.example.com',
            'disposable.style.email.with+symbol@example.com',
            'other.email-with-dash@example.com',
            '"very.unusual.@.unusual.com"@example.com',
            '"very.(),:;<>[]\".VERY.\"very@\ \"very\".unusual"@strange.example.com',
            '!#$%&\'*+-/=?^_`{}|~@example.org',
            '"()<>[]:,;@\"!#$%&\'*+-/=?^_`{}|~.a"@example.org',
            'user@[255.255.255.255]',
            'user@[IPv6:2001:db8:1ff::a0b:dbd0]',
            'user@[IPv6:2001:0db8:85a3:08d3:1319:8a2e:0370:7344]',
            'user@[IPv6:2001::7344]',
            'user@[IPv6:1111:2222:3333:4444:5555:6666:255.255.255.255]',
            'test@exämple.com',
            'test@ä.xe',
            'test@subexample.wizard',
            'test@wähwähwäh.ümläüts.de',
            '"tes@t"@wähwähwäh.ümläüts.de',
            'test@example.photography',
            'test@sub-domain.example.photography',
            'niceändsimple@example.com',
            'véry.çommon@example.com',
            'a.lîttle.lengthy.but.fiñe@dept.example.com',
            'dîsposable.style.émail.with+symbol@example.com',
            'other.émail-with-dash@example.com',
            '"verî.uñusual.@.uñusual.com"@example.com',
            '"verî.(),:;<>[]\".VERÎ.\"verî@\ \"verî\".unüsual"@strange.example.com',
            'üñîçøðé@example.com',
            '"üñîçøðé"@example.com',
            'ǅǼ੧ఘⅧ⒇৪@example.com',
            'üser@[255.255.255.255]',
            'üser@[IPv6:2001:db8:1ff::a0b:dbd0]',
            'üser@[IPv6:2001:0db8:85a3:08d3:1319:8a2e:0370:7344]',
            'üser@[IPv6:2001::7344]',
            'üser@[IPv6:1111:2222:3333:4444:5555:6666:255.255.255.255]',
            'tést@exämple.com',
            'tést@ä.xe',
            'tést@subexample.wizard',
            'tést@wähwähwäh.ümläüts.de',
            '"tés@t"@wähwähwäh.ümläüts.de',
            'tést@example.photography',
            'tést@sub-domain.example.photography',
            'tricky@example.com',
            'more-tricky@example.com',
            'even-more-tricky@example.com',
        ];

        $actual = StringUtil::extractEmail($text, '<a><strong>');

        sort($actual);
        sort($expected);

        $this->assertSame($expected, $actual);
    }
}
