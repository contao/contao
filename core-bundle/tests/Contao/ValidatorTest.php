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
            'Original: '.$email.' idna: '.Idna::encodeEmail($email),
        );
    }

    public static function emailProvider(): iterable
    {
        // Valid ASCII
        yield ['niceandsimple@example.com', true];
        yield ['very.common@example.com', true];
        yield ['a.little.lengthy.but.fine@dept.example.com', true];
        yield ['disposable.style.email.with+symbol@example.com', true];
        yield ['other.email-with-dash@example.com', true];
        yield ['"very.unusual.@.unusual.com"@example.com', true];
        yield ['"very.(),:;<>[]\".VERY.\"very@\ \"very\".unusual"@strange.example.com', true];
        yield ['!#$%&\'*+-/=?^_`{}|~@example.org', true];
        yield ['"()<>[]:,;@\"!#$%&\'*+-/=?^_`{}|~.a"@example.org', true];

        // Valid with IP addresses
        yield ['user@[255.255.255.255]', true];
        yield ['user@[IPv6:2001:db8:1ff::a0b:dbd0]', true];
        yield ['user@[IPv6:2001:0db8:85a3:08d3:1319:8a2e:0370:7344]', true];
        yield ['user@[IPv6:2001::7344]', true];
        yield ['user@[IPv6:1111:2222:3333:4444:5555:6666:255.255.255.255]', true];

        // Valid with IDNA domains
        yield ['test@exämple.com', true];
        yield ['test@ä.xe', true];
        yield ['test@subexample.wizard', true];
        yield ['test@wähwähwäh.ümläüts.de', true];
        yield ['"tes@t"@wähwähwäh.ümläüts.de', true];

        // Valid with new TLDs
        yield ['test@example.photography', true];
        yield ['test@sub-domain.example.photography', true];

        // Valid with Unicode characters in the local part
        yield ['niceändsimple@example.com', true];
        yield ['véry.çommon@example.com', true];
        yield ['a.lîttle.lengthy.but.fiñe@dept.example.com', true];
        yield ['dîsposable.style.émail.with+symbol@example.com', true];
        yield ['other.émail-with-dash@example.com', true];
        yield ['"verî.uñusual.@.uñusual.com"@example.com', true];
        yield ['"verî.(),:;<>[]\".VERÎ.\"verî@\ \"verî\".unüsual"@strange.example.com', true];
        yield ['üñîçøðé@example.com', true];
        yield ['"üñîçøðé"@example.com', true];
        yield ['ǅǼ੧ఘⅧ⒇৪@example.com', true];

        // Valid with IP addresses and Unicode characters in the local part
        yield ['üser@[255.255.255.255]', true];
        yield ['üser@[IPv6:2001:db8:1ff::a0b:dbd0]', true];
        yield ['üser@[IPv6:2001:0db8:85a3:08d3:1319:8a2e:0370:7344]', true];
        yield ['üser@[IPv6:2001::7344]', true];
        yield ['üser@[IPv6:1111:2222:3333:4444:5555:6666:255.255.255.255]', true];

        // Valid with IDNA domains and Unicode characters in the local part
        yield ['tést@exämple.com', true];
        yield ['tést@ä.xe', true];
        yield ['tést@subexample.wizard', true];
        yield ['tést@wähwähwäh.ümläüts.de', true];
        yield ['"tés@t"@wähwähwäh.ümläüts.de', true];

        // Valid with new TLDs and Unicode characters in the local part
        yield ['tést@example.photography', true];
        yield ['tést@sub-domain.example.photography', true];

        // Invalid ASCII
        yield ['test..child@example.com', false];
        yield ['test@sub.-example.com', false];
        yield ['test@_smtp_.example.com', false];
        yield ['test@sub..example.com', false];
        yield ['test@subexamplecom', false];
        yield ['Abc.example.com', false];
        yield ['A@b@c@example.com', false];
        yield ['a"b(c)d,e:f;gi[j\k]l@example.com', false];
        yield ['just"not"right@example.com', false];
        yield ['this is"not\allowed@example.com', false];
        yield ['this\ still\"not\allowed@example.com', false];
        yield ['(comment)test@iana.org', false];
        yield ['test@[1.2.3.4', false];
        yield ['test@iana.org-', false];
        yield ['', false];
        yield ['test', false];
        yield ['@', false];
        yield ['test@', false];

        // Invalid with IP addresses
        yield ['test@a[255.255.255.255]', false];
        yield ['test@[255.255.255]', false];
        yield ['test@[255.255.255.255.255]', false];
        yield ['test@[255.255.255.256]', false];
        yield ['test@[2001::7344]', false];
        yield ['test@[IPv6:1111:2222:3333:4444:5555:6666:7777:255.255.255.255]', false];

        // Invalid with IDNA domain
        yield ['tes@t@wähwähwäh.ümläüts.de', false];
        yield [' test@wähwähwäh.ümläüts.de', false];

        // Invalid with new TLDs
        yield ['tes@t@example.photography', false];
        yield [' test@sub-domain.example.photography', false];

        // Invalid with Unicode characters in the local part
        yield ['tést..child@example.com', false];
        yield ['tést@sub.-example.com', false];
        yield ['tést@_smtp_.example.com', false];
        yield ['tést@sub..example.com', false];
        yield ['tést@subexamplecom', false];
        yield ['Abç.example.com', false];
        yield ['Â@ఘ@ç@example.com', false];
        yield ['â"ఘ(ç)d,e:f;gi[j\k]l@example.com', false];
        yield ['jüst"not"rîght@example.com', false];
        yield ['this îs"not\alløwed@example.com', false];
        yield ['this\ stîll\"not\alløwed@example.com', false];
        yield ['(çommént)tést@iana.org', false];
        yield ['tést@[1.2.3.4', false];
        yield ['tést@iana.org-', false];
        yield ['tést@', false];

        // Invalid with IP addresses and Unicode characters in the local part
        yield ['tést@a[255.255.255.255]', false];
        yield ['tést@[255.255.255]', false];
        yield ['tést@[255.255.255.255.255]', false];
        yield ['tést@[255.255.255.256]', false];
        yield ['tést@[2001::7344]', false];
        yield ['tést@[IPv6:1111:2222:3333:4444:5555:6666:7777:255.255.255.255]', false];

        // Invalid with IDNA domains and Unicode characters in the local part
        yield ['tés@t@wähwähwäh.ümläüts.de', false];
        yield [' tést@wähwähwäh.ümläüts.de', false];

        // Invalid with new TLDs and Unicode characters in the local part
        yield ['tés@t@example.photography', false];
        yield [' tést@sub-domain.example.photography', false];
    }

    public function testExtractsEmailAddressesFromText(): void
    {
        $text = <<<'EOF'
            This is a niceandsimple@example.com and this a very.common@example.com. Another little.lengthy.but.fine@dept.example.com and also a disposable.style.email.with+symbol@example.com or an other.email-with-dash@example.com. There are "very.unusual.@.unusual.com"@example.com and "very.(),:;<>[]\".VERY.\"very@\ \"very\".unusual"@strange.example.com and even !#$%&'*+-/=?^_`{}|~@example.org or "()<>[]:,;@\"!#$%&'*+-/=?^_`{}|~.a"@example.org but they are all valid.
            IP addresses as in user@[255.255.255.255], user@[IPv6:2001:db8:1ff::a0b:dbd0], user@[IPv6:2001:0db8:85a3:08d3:1319:8a2e:0370:7344], user@[IPv6:2001::7344] or user@[IPv6:1111:2222:3333:4444:5555:6666:255.255.255.255] are valid, too.
            We also support IDNA domains as in test@exämple.com, test@ä.xe, test@subexample.wizard, test@wähwähwäh.ümläüts.de or "tes@t"@wähwähwäh.ümläüts.de. And we support new TLDs as in test@example.photography or test@sub-domain.example.photography.
            And we support Unicode characters in the local part (RFC 6531) as in niceändsimple@example.com, véry.çommon@example.com, a.lîttle.lengthy.but.fiñe@dept.example.com, dîsposable.style.émail.with+symbol@example.com, other.émail-with-dash@example.com, "verî.uñusual.@.uñusual.com"@example.com, "verî.(),:;<>[]\".VERÎ.\"verî@\ \"verî\".unüsual"@strange.example.com, üñîçøðé@example.com, "üñîçøðé"@example.com or ǅǼ੧ఘⅧ⒇৪@example.com.
            Of course also with IP addresses: üser@[255.255.255.255], üser@[IPv6:2001:db8:1ff::a0b:dbd0], üser@[IPv6:2001:0db8:85a3:08d3:1319:8a2e:0370:7344], üser@[IPv6:2001::7344] or üser@[IPv6:1111:2222:3333:4444:5555:6666:255.255.255.255] and Unicode characters in the local part: tést@exämple.com, tést@ä.xe, tést@subexample.wizard, tést@wähwähwäh.ümläüts.de, "tés@t"@wähwähwäh.ümläüts.de. New TLDs? No problem: tést@example.photography or tést@sub-domain.example.photography.
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
