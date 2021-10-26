<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Intl;

use Contao\CoreBundle\InsertTags\Parser;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Interop\ChunkedText;
use Contao\System;

class ParserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = $this->getContainerWithContaoConfiguration($this->getTempDir());

        $container->set('contao.security.token_checker', $this->createMock(TokenChecker::class));

        System::setContainer($container);
    }

    public function testReplace(): void
    {
        $this->assertSame('<br>', (new Parser())->replace('{{br}}'));

        $this->assertSame(
            [[ChunkedText::TYPE_RAW, '<br>']],
            iterator_to_array((new Parser())->replaceChunked('{{br}}'))
        );
    }

    public function testRender(): void
    {
        $this->assertSame('<br>', (new Parser())->render('br'));
    }
}
