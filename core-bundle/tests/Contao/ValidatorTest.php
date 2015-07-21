<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Contao;

use Contao\CoreBundle\Test\TestCase;
use Contao\Validator;

/**
 * Tests the Validator class.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class ValidatorTest extends TestCase
{
    /**
     * Data provider for Validator::isEmail().
     *
     * @return array
     */
    public function emailProvider()
    {
        return [
            // Valid ones in all ugly permutations but allowed accordingly to various RFCs.
            ['niceandsimple@example.com', true],
            ['very.common@example.com', true],
            ['a.little.lengthy.but.fine@dept.example.com', true],
            ['disposable.style.email.with+symbol@example.com', true],
            ['user@[IPv6:2001:db8:1ff::a0b:dbd0]', true],
            ['"very.unusual.@.unusual.com"@example.com', true],
            ['"very.(),:;<>[]\".VERY.\"very@\ \"very\".unusual"@strange.example.com', true],
            ['!#$%&\'*+-/=?^_`{}|~@example.org', true],
            ['"()<>[]:,;@\\"!#$%&\'*+-/=?^_`{}|~.a"@example.org', true],
            ['test@example.com', true],
            ['test.child@example.com', true],
            ['test@exämple.com', true],
            ['test@ä-.xe', true],
            ['test@subexample.wizard', true],
            ['test@wähwähwäh.ümläüts.de', true],
            ['"tes@t"@wähwähwäh.ümläüts.de', true],
            ['test@[255.255.255.255]', true],
            ['test@[IPv6:2001:0db8:85a3:08d3:1319:8a2e:0370:7344]', true],
            ['test@[IPv6:2001::7344]', true],
            ['test@[IPv6:1111:2222:3333:4444:5555:6666:255.255.255.255]', true],
            ['test+reference@example.com', true],

            // Invalid ones in even more uglier permutations and all not allowed by RFCs.
            ['test..child@example.com', false],
            ['test@sub.-example.com', false],
            ['test@_smtp_.example.com', false],
            ['test@sub..example.com', false],
            ['test@subexamplecom', false],
            ['tes@t@wähwähwäh.ümläüts.de', false],
            [' test@wähwähwäh.ümläüts.de', false],
            ['Abc.example.com', false],
            ['A@b@c@example.com', false],
            ['a"b(c)d,e:f;gi[j\k]l@example.com', false],
            ['just"not"right@example.com', false],
            ['this is"not\allowed@example.com', false],
            ['this\ still\"not\allowed@example.com', false],
            ['test@a[255.255.255.255]', false],
            ['test@[255.255.255]', false],
            ['test@[255.255.255.255.255]', false],
            ['test@[255.255.255.256]', false],
            ['test@[2001::7344]', false],
            ['test@[IPv6:1111:2222:3333:4444:5555:6666:7777:255.255.255.255]', false],
            ['(comment)test@iana.org', false],
            ['test@[1.2.3.4', false],
            ['test@iana.org-', false],
            ['', false],
            ['test', false],
            ['@', false],
            ['test@', false],
        ];
    }

    /**
     * Test Validator::isEmail().
     *
     * @param string $email    The email to test.
     *
     * @param bool   $expected The expected result.
     *
     * @dataProvider emailProvider
     */
    public function testEmail($email, $expected)
    {
        $this->assertEquals($expected, Validator::isEmail($email), 'Original: ' . $email . ' idna: ' . \Contao\Idna::encodeEmail($email));
    }
}
