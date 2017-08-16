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
use Contao\StringUtil;
use Contao\Validator;
use PHPUnit\Framework\TestCase;

/**
 * Tests the Validator class.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 * @author Leo Feyer <https://github.com/leofeyer>
 *
 * @group contao3
 */
class ValidatorTest extends TestCase
{
    /**
     * Tests the isEmail() method.
     *
     * @param string $email
     * @param bool   $expected
     *
     * @dataProvider emailProvider
     */
    public function testEmail($email, $expected)
    {
        $this->assertSame(
            $expected,
            Validator::isEmail($email),
            'Original: '.$email.' idna: '.Idna::encodeEmail($email)
        );
    }

    /**
     * Tests the StringUtil::extactEmail() method.
     */
    public function testExtractEmail()
    {
        $text = <<<EOF
This is a niceandsimple@example.com and this a very.common@example.com. Another little.lengthy.but.fine@dept.example.com and also a disposable.style.email.with+symbol@example.com or an other.email-with-dash@example.com. There are "very.unusual.@.unusual.com"@example.com and "very.(),:;<>[]\".VERY.\"very@\ \"very\".unusual"@strange.example.com and even !#$%&'*+-/=?^_`{}|~@example.org or "()<>[]:,;@\\"!#$%&'*+-/=?^_`{}|~.a"@example.org but they are all valid.
IP addresses as in user@[255.255.255.255], user@[IPv6:2001:db8:1ff::a0b:dbd0], user@[IPv6:2001:0db8:85a3:08d3:1319:8a2e:0370:7344], user@[IPv6:2001::7344] or user@[IPv6:1111:2222:3333:4444:5555:6666:255.255.255.255] are valid, too.
We also support IDNA domains as in test@exämple.com, test@ä-.xe, test@subexample.wizard, test@wähwähwäh.ümläüts.de or "tes@t"@wähwähwäh.ümläüts.de. And we support new TLDs as in test@example.photography or test@sub-domain.example.photography.
And we support unicode characters in the local part (RFC 6531) as in niceändsimple@example.com, véry.çommon@example.com, a.lîttle.lengthy.but.fiñe@dept.example.com, dîsposable.style.émail.with+symbol@example.com, other.émail-with-dash@example.com, "verî.uñusual.@.uñusual.com"@example.com, "verî.(),:;<>[]\".VERÎ.\"verî@\ \"verî\".unüsual"@strange.example.com, üñîçøðé@example.com, "üñîçøðé"@example.com or ǅǼ੧ఘⅧ⒇৪@example.com.
Of course also with IP addresses: üser@[255.255.255.255], üser@[IPv6:2001:db8:1ff::a0b:dbd0], üser@[IPv6:2001:0db8:85a3:08d3:1319:8a2e:0370:7344], üser@[IPv6:2001::7344] or üser@[IPv6:1111:2222:3333:4444:5555:6666:255.255.255.255] and unicode characters in the local part: tést@exämple.com, tést@ä-.xe, tést@subexample.wizard, tést@wähwähwäh.ümläüts.de, "tés@t"@wähwähwäh.ümläüts.de. New TLDs? No problem: tést@example.photography or tést@sub-domain.example.photography.
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
            'test@ä-.xe',
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
            'tést@ä-.xe',
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

    /**
     * Provides the data for the testEmail() method.
     *
     * @return array
     */
    public function emailProvider()
    {
        return [
            // Valid ASCII
            ['niceandsimple@example.com', 1],
            ['very.common@example.com', 1],
            ['a.little.lengthy.but.fine@dept.example.com', 1],
            ['disposable.style.email.with+symbol@example.com', 1],
            ['other.email-with-dash@example.com', 1],
            ['"very.unusual.@.unusual.com"@example.com', 1],
            ['"very.(),:;<>[]\".VERY.\"very@\ \"very\".unusual"@strange.example.com', 1],
            ['!#$%&\'*+-/=?^_`{}|~@example.org', 1],
            ['"()<>[]:,;@\"!#$%&\'*+-/=?^_`{}|~.a"@example.org', 1],

            // Valid with IP addresses
            ['user@[255.255.255.255]', 1],
            ['user@[IPv6:2001:db8:1ff::a0b:dbd0]', 1],
            ['user@[IPv6:2001:0db8:85a3:08d3:1319:8a2e:0370:7344]', 1],
            ['user@[IPv6:2001::7344]', 1],
            ['user@[IPv6:1111:2222:3333:4444:5555:6666:255.255.255.255]', 1],

            // Valid with IDNA domains
            ['test@exämple.com', 1],
            ['test@ä-.xe', 1],
            ['test@subexample.wizard', 1],
            ['test@wähwähwäh.ümläüts.de', 1],
            ['"tes@t"@wähwähwäh.ümläüts.de', 1],

            // Valid with new TLDs
            ['test@example.photography', 1],
            ['test@sub-domain.example.photography', 1],

            // Valid with unicode characters in the local part
            ['niceändsimple@example.com', 1],
            ['véry.çommon@example.com', 1],
            ['a.lîttle.lengthy.but.fiñe@dept.example.com', 1],
            ['dîsposable.style.émail.with+symbol@example.com', 1],
            ['other.émail-with-dash@example.com', 1],
            ['"verî.uñusual.@.uñusual.com"@example.com', 1],
            ['"verî.(),:;<>[]\".VERÎ.\"verî@\ \"verî\".unüsual"@strange.example.com', 1],
            ['üñîçøðé@example.com', 1],
            ['"üñîçøðé"@example.com', 1],
            ['ǅǼ੧ఘⅧ⒇৪@example.com', 1],

            // Valid with IP addresses and unicode characters in the local part
            ['üser@[255.255.255.255]', 1],
            ['üser@[IPv6:2001:db8:1ff::a0b:dbd0]', 1],
            ['üser@[IPv6:2001:0db8:85a3:08d3:1319:8a2e:0370:7344]', 1],
            ['üser@[IPv6:2001::7344]', 1],
            ['üser@[IPv6:1111:2222:3333:4444:5555:6666:255.255.255.255]', 1],

            // Valid with IDNA domains and unicode characters in the local part
            ['tést@exämple.com', 1],
            ['tést@ä-.xe', 1],
            ['tést@subexample.wizard', 1],
            ['tést@wähwähwäh.ümläüts.de', 1],
            ['"tés@t"@wähwähwäh.ümläüts.de', 1],

            // Valid with new TLDs and unicode characters in the local part
            ['tést@example.photography', 1],
            ['tést@sub-domain.example.photography', 1],

            // Invalid ASCII
            ['test..child@example.com', 0],
            ['test@sub.-example.com', 0],
            ['test@_smtp_.example.com', 0],
            ['test@sub..example.com', 0],
            ['test@subexamplecom', 0],
            ['Abc.example.com', 0],
            ['A@b@c@example.com', 0],
            ['a"b(c)d,e:f;gi[j\k]l@example.com', 0],
            ['just"not"right@example.com', 0],
            ['this is"not\allowed@example.com', 0],
            ['this\ still\"not\allowed@example.com', 0],
            ['(comment)test@iana.org', 0],
            ['test@[1.2.3.4', 0],
            ['test@iana.org-', 0],
            ['', 0],
            ['test', 0],
            ['@', 0],
            ['test@', 0],

            // Invalid with IP addresses
            ['test@a[255.255.255.255]', 0],
            ['test@[255.255.255]', 0],
            ['test@[255.255.255.255.255]', 0],
            ['test@[255.255.255.256]', 0],
            ['test@[2001::7344]', 0],
            ['test@[IPv6:1111:2222:3333:4444:5555:6666:7777:255.255.255.255]', 0],

            // Invalid with IDNA domain
            ['tes@t@wähwähwäh.ümläüts.de', 0],
            [' test@wähwähwäh.ümläüts.de', 0],

            // Invalid with new TLDs
            ['tes@t@example.photography', 0],
            [' test@sub-domain.example.photography', 0],

            // Invalid with unicode characters in the local part
            ['tést..child@example.com', 0],
            ['tést@sub.-example.com', 0],
            ['tést@_smtp_.example.com', 0],
            ['tést@sub..example.com', 0],
            ['tést@subexamplecom', 0],
            ['Abç.example.com', 0],
            ['Â@ఘ@ç@example.com', 0],
            ['â"ఘ(ç)d,e:f;gi[j\k]l@example.com', 0],
            ['jüst"not"rîght@example.com', 0],
            ['this îs"not\alløwed@example.com', 0],
            ['this\ stîll\"not\alløwed@example.com', 0],
            ['(çommént)tést@iana.org', 0],
            ['tést@[1.2.3.4', 0],
            ['tést@iana.org-', 0],
            ['tést@', 0],

            // Invalid with IP addresses and unicode characters in the local part
            ['tést@a[255.255.255.255]', 0],
            ['tést@[255.255.255]', 0],
            ['tést@[255.255.255.255.255]', 0],
            ['tést@[255.255.255.256]', 0],
            ['tést@[2001::7344]', 0],
            ['tést@[IPv6:1111:2222:3333:4444:5555:6666:7777:255.255.255.255]', 0],

            // Invalid with IDNA domains and unicode characters in the local part
            ['tés@t@wähwähwäh.ümläüts.de', 0],
            [' tést@wähwähwäh.ümläüts.de', 0],

            // Invalid with new TLDs and unicode characters in the local part
            ['tés@t@example.photography', 0],
            [' tést@sub-domain.example.photography', 0],
        ];
    }
}
