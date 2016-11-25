<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Contao;

use Contao\Idna;
use Contao\StringUtil;
use Contao\Validator;

/**
 * Tests the Validator class.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 *
 * @group legacy
 */
class ValidatorTest extends \PHPUnit_Framework_TestCase
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
        $this->assertEquals(
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

        $this->assertEquals($expected, $actual);
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
            ['niceandsimple@example.com', true],
            ['very.common@example.com', true],
            ['a.little.lengthy.but.fine@dept.example.com', true],
            ['disposable.style.email.with+symbol@example.com', true],
            ['other.email-with-dash@example.com', true],
            ['"very.unusual.@.unusual.com"@example.com', true],
            ['"very.(),:;<>[]\".VERY.\"very@\ \"very\".unusual"@strange.example.com', true],
            ['!#$%&\'*+-/=?^_`{}|~@example.org', true],
            ['"()<>[]:,;@\"!#$%&\'*+-/=?^_`{}|~.a"@example.org', true],

            // Valid with IP addresses
            ['user@[255.255.255.255]', true],
            ['user@[IPv6:2001:db8:1ff::a0b:dbd0]', true],
            ['user@[IPv6:2001:0db8:85a3:08d3:1319:8a2e:0370:7344]', true],
            ['user@[IPv6:2001::7344]', true],
            ['user@[IPv6:1111:2222:3333:4444:5555:6666:255.255.255.255]', true],

            // Valid with IDNA domains
            ['test@exämple.com', true],
            ['test@ä-.xe', true],
            ['test@subexample.wizard', true],
            ['test@wähwähwäh.ümläüts.de', true],
            ['"tes@t"@wähwähwäh.ümläüts.de', true],

            // Valid with new TLDs
            ['test@example.photography', true],
            ['test@sub-domain.example.photography', true],

            // Valid with unicode characters in the local part
            ['niceändsimple@example.com', true],
            ['véry.çommon@example.com', true],
            ['a.lîttle.lengthy.but.fiñe@dept.example.com', true],
            ['dîsposable.style.émail.with+symbol@example.com', true],
            ['other.émail-with-dash@example.com', true],
            ['"verî.uñusual.@.uñusual.com"@example.com', true],
            ['"verî.(),:;<>[]\".VERÎ.\"verî@\ \"verî\".unüsual"@strange.example.com', true],
            ['üñîçøðé@example.com', true],
            ['"üñîçøðé"@example.com', true],
            ['ǅǼ੧ఘⅧ⒇৪@example.com', true],

            // Valid with IP addresses and unicode characters in the local part
            ['üser@[255.255.255.255]', true],
            ['üser@[IPv6:2001:db8:1ff::a0b:dbd0]', true],
            ['üser@[IPv6:2001:0db8:85a3:08d3:1319:8a2e:0370:7344]', true],
            ['üser@[IPv6:2001::7344]', true],
            ['üser@[IPv6:1111:2222:3333:4444:5555:6666:255.255.255.255]', true],

            // Valid with IDNA domains and unicode characters in the local part
            ['tést@exämple.com', true],
            ['tést@ä-.xe', true],
            ['tést@subexample.wizard', true],
            ['tést@wähwähwäh.ümläüts.de', true],
            ['"tés@t"@wähwähwäh.ümläüts.de', true],

            // Valid with new TLDs and unicode characters in the local part
            ['tést@example.photography', true],
            ['tést@sub-domain.example.photography', true],

            // Invalid ASCII
            ['test..child@example.com', false],
            ['test@sub.-example.com', false],
            ['test@_smtp_.example.com', false],
            ['test@sub..example.com', false],
            ['test@subexamplecom', false],
            ['Abc.example.com', false],
            ['A@b@c@example.com', false],
            ['a"b(c)d,e:f;gi[j\k]l@example.com', false],
            ['just"not"right@example.com', false],
            ['this is"not\allowed@example.com', false],
            ['this\ still\"not\allowed@example.com', false],
            ['(comment)test@iana.org', false],
            ['test@[1.2.3.4', false],
            ['test@iana.org-', false],
            ['', false],
            ['test', false],
            ['@', false],
            ['test@', false],

            // Invalid with IP addresses
            ['test@a[255.255.255.255]', false],
            ['test@[255.255.255]', false],
            ['test@[255.255.255.255.255]', false],
            ['test@[255.255.255.256]', false],
            ['test@[2001::7344]', false],
            ['test@[IPv6:1111:2222:3333:4444:5555:6666:7777:255.255.255.255]', false],

            // Invalid with IDNA domain
            ['tes@t@wähwähwäh.ümläüts.de', false],
            [' test@wähwähwäh.ümläüts.de', false],

            // Invalid with new TLDs
            ['tes@t@example.photography', false],
            [' test@sub-domain.example.photography', false],

            // Invalid with unicode characters in the local part
            ['tést..child@example.com', false],
            ['tést@sub.-example.com', false],
            ['tést@_smtp_.example.com', false],
            ['tést@sub..example.com', false],
            ['tést@subexamplecom', false],
            ['Abç.example.com', false],
            ['Â@ఘ@ç@example.com', false],
            ['â"ఘ(ç)d,e:f;gi[j\k]l@example.com', false],
            ['jüst"not"rîght@example.com', false],
            ['this îs"not\alløwed@example.com', false],
            ['this\ stîll\"not\alløwed@example.com', false],
            ['(çommént)tést@iana.org', false],
            ['tést@[1.2.3.4', false],
            ['tést@iana.org-', false],
            ['tést@', false],

            // Invalid with IP addresses and unicode characters in the local part
            ['tést@a[255.255.255.255]', false],
            ['tést@[255.255.255]', false],
            ['tést@[255.255.255.255.255]', false],
            ['tést@[255.255.255.256]', false],
            ['tést@[2001::7344]', false],
            ['tést@[IPv6:1111:2222:3333:4444:5555:6666:7777:255.255.255.255]', false],

            // Invalid with IDNA domains and unicode characters in the local part
            ['tés@t@wähwähwäh.ümläüts.de', false],
            [' tést@wähwähwäh.ümläüts.de', false],

            // Invalid with new TLDs and unicode characters in the local part
            ['tés@t@example.photography', false],
            [' tést@sub-domain.example.photography', false],
        ];
    }
}
