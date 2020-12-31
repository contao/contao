<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Extension;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Compat\ScalarValueHolder;
use Contao\CoreBundle\Twig\Extension\ContaoCompatExtension;
use Twig\Environment;
use Twig\Loader\LoaderInterface;

class ContaoCompatExtensionTest extends TestCase
{
    public function testRegistersSafeClass(): void
    {
        $twig = new Environment($this->createMock(LoaderInterface::class));
        $twig->addExtension(new ContaoCompatExtension($twig));

        // Escape a value with autoescape enabled
        $escape = static function ($value) use ($twig): string {
            return twig_escape_filter($twig, $value, 'html', null, true);
        };

        $string = '<p>bar</p>';
        $proxyObject = new ScalarValueHolder('<p>bar</p>', 'name');

        $this->assertSame('&lt;p&gt;bar&lt;/p&gt;', $escape($string));
        $this->assertSame('<p>bar</p>', $escape($proxyObject));
    }
}
