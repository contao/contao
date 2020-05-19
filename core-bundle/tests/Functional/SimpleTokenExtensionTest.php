<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SimpleTokenExtensionTest extends KernelTestCase
{
    public function testSimpleTokenExtensionGetsRegistered(): void
    {
        self::bootKernel();

        $simpleTokenParser = self::$container->get('contao.util.simple_token_parser');

        $this->assertSame(
            'Custom function evaluated!',
            $simpleTokenParser->parseTokens("Custom function {if strtoupper(token) === 'FOO'}evaluated!{endif}", ['token' => 'foo'])
        );
    }
}
